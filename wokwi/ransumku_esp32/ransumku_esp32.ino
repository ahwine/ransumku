/**
 * =========================================================================
 * RANSUMKU - FIRMWARE ESP32 FISIK (Arduino IDE)
 * Terhubung ke web RansumKu lewat WiFi. Jika server memvalidasi data,
 * LED BIRU bawaan ESP32 (GPIO 2) menyala terus.
 *
 * ALUR:
 *   ESP32 --POST suhu & kelembaban--> /api/kandang/{FARM_ID}/sensor
 *   Server balas JSON berisi status relay (kipas, misting, lampu, pakan)
 *   ESP32 menjalankan relay & motor sesuai balasan Pusat Kendali web.
 *
 * LED BIRU (GPIO 2):
 *   Menyala terus   = TERVALIDASI (server menerima data & membalas ok)
 *   Kedip lambat    = WiFi tersambung, tetapi server belum valid
 *                     (server mati / IP salah / token salah)
 *   Kedip cepat     = WiFi belum tersambung
 *
 * JIKA OFFLINE: kipas & sprinkler berjalan otomatis memakai logika THI lokal
 * (failsafe), kereta pakan berhenti.
 *
 * LIBRARY (Library Manager Arduino IDE):
 *   - DHT sensor library for ESPx   (by beegee_tokyo)  -> "DHTesp.h"
 *   - Adafruit SSD1306
 *   - Adafruit GFX Library
 * BOARD: ESP32 Dev Module (esp32 by Espressif Systems)
 * =========================================================================
 */

#include <WiFi.h>
#include <HTTPClient.h>
#include <Wire.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>
#include "DHTesp.h"

// =========================== KONFIGURASI ===============================
const char* WIFI_SSID     = "WARKOP BENING - DECO";
const char* WIFI_PASSWORD = "specialtea";

// IP laptop yang menjalankan: php artisan serve --host=0.0.0.0 --port=8000
// ESP32 & laptop harus di WiFi yang sama.
const char* SERVER_URL    = "http://192.168.68.171:8000";
const int   FARM_ID       = 1;
const char* IOT_TOKEN     = "";   // kosongkan bila kandang tidak memakai token

const unsigned long INTERVAL_KIRIM_MS = 3000;   // jeda kirim data ke server
const unsigned long VALID_TIMEOUT_MS  = 10000;  // dianggap tidak valid bila tak ada balasan ok selama ini

// Kebanyakan modul relay 2/4 channel aktif LOW. Ubah ke false bila relay aktif HIGH.
const bool RELAY_ACTIVE_LOW = true;

// ============================== PIN ====================================
const int PIN_DHT             = 15;  // DHT22
const int PIN_MOTOR_IN1       = 18;  // L298N IN1 (maju)
const int PIN_MOTOR_IN2       = 19;  // L298N IN2 (mundur). ENA dibiarkan terpasang jumper
const int PIN_RELAY_KIPAS     = 12;  // Relay kipas blower
const int PIN_RELAY_SPRINKLER = 14;  // Relay pompa sprinkler atap (misting)
const int PIN_RELAY_LAMPU     = 27;  // Relay lampu (opsional, abaikan bila belum dipasang)
const int PIN_LED_BIRU        = 2;   // LED biru bawaan board
const int PIN_LIMIT_A         = 33;  // Limit switch pangkal (titik A)
const int PIN_LIMIT_B         = 32;  // Limit switch ujung (titik B)

#define SCREEN_WIDTH 128
#define SCREEN_HEIGHT 64
Adafruit_SSD1306 display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, -1);
DHTesp dhtSensor;

// ============================ STATE ====================================
volatile float curSuhu = 0;
volatile float curRH   = 0;
volatile bool  sensorSiap = false;

volatile bool validated = false;
volatile unsigned long lastValidMs = 0;
volatile int  lastHttpCode = 0;

volatile bool srvKipas = false;
volatile bool srvMisting = false;
volatile bool srvLampu = false;
volatile bool srvPakan = false;

bool aktKipas = false;
bool aktSprinkler = false;
bool aktLampu = false;

enum FeederState { FEEDER_IDLE, FEEDER_MAJU, FEEDER_JEDA_B, FEEDER_MUNDUR, FEEDER_JEDA_A };
FeederState feeder = FEEDER_IDLE;
unsigned long feederMulai = 0;
const unsigned long JALAN_MAKS_MS = 15000;  // pengaman bila limit switch tidak tersentuh
const unsigned long JEDA_MS = 2000;

unsigned long lastSensorRead = 0;
unsigned long lastDisplay = 0;

// ============================ UTILITAS =================================
void setRelay(int pin, bool on) {
  bool level = RELAY_ACTIVE_LOW ? !on : on;
  digitalWrite(pin, level ? HIGH : LOW);
}

void motorMaju()   { digitalWrite(PIN_MOTOR_IN1, HIGH); digitalWrite(PIN_MOTOR_IN2, LOW); }
void motorMundur() { digitalWrite(PIN_MOTOR_IN1, LOW);  digitalWrite(PIN_MOTOR_IN2, HIGH); }
void motorStop()   { digitalWrite(PIN_MOTOR_IN1, LOW);  digitalWrite(PIN_MOTOR_IN2, LOW); }

float hitungTHI(float t, float rh) {
  // Zulovich & DeShazer (1990)
  return (0.8f * t) + ((rh / 100.0f) * (t - 14.4f)) + 46.4f;
}

bool isOnline() {
  return validated && (millis() - lastValidMs < VALID_TIMEOUT_MS);
}

bool bacaBool(const String& json, const char* key) {
  String k = String("\"") + key + "\":";
  int i = json.indexOf(k);
  if (i < 0) return false;
  return json.startsWith("true", i + k.length());
}

// ===================== KOMUNIKASI KE SERVER (TASK) =====================
void kirimDanValidasi() {
  HTTPClient http;
  String url = String(SERVER_URL) + "/api/kandang/" + String(FARM_ID) + "/sensor";

  float suhu = curSuhu;
  float rh = curRH;
  if (suhu < 10) suhu = 10;
  if (suhu > 55) suhu = 55;
  if (rh < 10) rh = 10;
  if (rh > 100) rh = 100;

  String body = String("{\"suhu\":") + String(suhu, 1) + ",\"kelembaban\":" + String(rh, 1);
  if (strlen(IOT_TOKEN) > 0) {
    body += String(",\"token\":\"") + IOT_TOKEN + "\"";
  }
  body += "}";

  http.setConnectTimeout(2000);
  http.setTimeout(3000);
  http.begin(url);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("Accept", "application/json");

  int code = http.POST(body);
  String resp = code > 0 ? http.getString() : String("");
  http.end();
  lastHttpCode = code;

  if (code == 200 && resp.indexOf("\"ok\":true") >= 0) {
    srvKipas   = bacaBool(resp, "relay_kipas");
    srvMisting = bacaBool(resp, "relay_misting");
    srvLampu   = bacaBool(resp, "relay_lampu");
    srvPakan   = bacaBool(resp, "relay_pakan");
    validated = true;
    lastValidMs = millis();
    Serial.printf("[SERVER] TERVALIDASI | kipas=%d misting=%d lampu=%d pakan=%d\n",
                  srvKipas, srvMisting, srvLampu, srvPakan);
  } else {
    validated = false;
    Serial.printf("[SERVER] TIDAK VALID | HTTP %d\n", code);
  }
}

void taskKomunikasi(void* param) {
  for (;;) {
    if (WiFi.status() != WL_CONNECTED) {
      validated = false;
      WiFi.reconnect();
      vTaskDelay(3000 / portTICK_PERIOD_MS);
      continue;
    }
    if (sensorSiap) {
      kirimDanValidasi();
    }
    vTaskDelay(INTERVAL_KIRIM_MS / portTICK_PERIOD_MS);
  }
}

// ======================== KONTROL AKTUATOR =============================
void updateAktuator() {
  float suhu = curSuhu;
  float thi = hitungTHI(curSuhu, curRH);

  if (isOnline()) {
    aktKipas = srvKipas;
    aktSprinkler = srvMisting;
    aktLampu = srvLampu;
  } else if (sensorSiap) {
    // Failsafe lokal saat server tidak tersedia (histeresis sederhana)
    if (suhu >= 31.0f || thi >= 78.0f) aktKipas = true;
    else if (suhu < 30.0f && thi < 76.0f) aktKipas = false;

    if (suhu >= 33.0f || thi >= 80.0f) aktSprinkler = true;
    else if (suhu < 31.5f && thi < 78.0f) aktSprinkler = false;
  }

  setRelay(PIN_RELAY_KIPAS, aktKipas);
  setRelay(PIN_RELAY_SPRINKLER, aktSprinkler);
  setRelay(PIN_RELAY_LAMPU, aktLampu);
}

void updateFeeder() {
  unsigned long now = millis();
  bool wanted = isOnline() && srvPakan;

  if (!wanted) {
    if (feeder != FEEDER_IDLE) {
      motorStop();
      feeder = FEEDER_IDLE;
      Serial.println("[PAKAN] Dihentikan.");
    }
    return;
  }

  switch (feeder) {
    case FEEDER_IDLE:
      feeder = FEEDER_MAJU;
      feederMulai = now;
      motorMaju();
      Serial.println("[PAKAN] Maju (A -> B)");
      break;

    case FEEDER_MAJU:
      if (digitalRead(PIN_LIMIT_B) == LOW || now - feederMulai >= JALAN_MAKS_MS) {
        motorStop();
        feeder = FEEDER_JEDA_B;
        feederMulai = now;
        Serial.println("[PAKAN] Sampai B, jeda.");
      }
      break;

    case FEEDER_JEDA_B:
      if (now - feederMulai >= JEDA_MS) {
        feeder = FEEDER_MUNDUR;
        feederMulai = now;
        motorMundur();
        Serial.println("[PAKAN] Mundur (B -> A)");
      }
      break;

    case FEEDER_MUNDUR:
      if (digitalRead(PIN_LIMIT_A) == LOW || now - feederMulai >= JALAN_MAKS_MS) {
        motorStop();
        feeder = FEEDER_JEDA_A;
        feederMulai = now;
        Serial.println("[PAKAN] Sampai A, jeda.");
      }
      break;

    case FEEDER_JEDA_A:
      if (now - feederMulai >= JEDA_MS) {
        feeder = FEEDER_MAJU;
        feederMulai = now;
        motorMaju();
        Serial.println("[PAKAN] Maju (A -> B)");
      }
      break;
  }
}

// ============================ LED BIRU =================================
void updateLedBiru() {
  unsigned long now = millis();
  if (isOnline()) {
    digitalWrite(PIN_LED_BIRU, HIGH);                    // tervalidasi: biru menyala terus
  } else if (WiFi.status() == WL_CONNECTED) {
    digitalWrite(PIN_LED_BIRU, (now / 500) % 2);         // WiFi ok, server belum valid: kedip lambat
  } else {
    digitalWrite(PIN_LED_BIRU, (now / 125) % 2);         // WiFi putus: kedip cepat
  }
}

// ============================== OLED ===================================
void renderDisplay() {
  float thi = hitungTHI(curSuhu, curRH);
  const char* labelTHI = "NYAMAN";
  if (thi >= 84.0f) labelTHI = "BAHAYA";
  else if (thi >= 78.0f) labelTHI = "KRITIS";
  else if (thi >= 72.0f) labelTHI = "WASPADA";

  display.clearDisplay();
  display.setTextColor(SSD1306_WHITE);
  display.setTextSize(1);
  display.setCursor(8, 0);
  display.print("RANSUMKU - SMART FARM");
  display.drawLine(0, 10, 127, 10, SSD1306_WHITE);

  display.setCursor(0, 14);
  display.printf("Suhu: %.1fC", (float)curSuhu);
  display.setCursor(76, 14);
  display.printf("RH:%d%%", (int)curRH);

  display.setCursor(0, 25);
  display.printf("THI : %.1f %s", thi, labelTHI);

  display.setCursor(0, 36);
  display.printf("Kipas:%s Atap:%s", aktKipas ? "ON" : "OFF", aktSprinkler ? "ON" : "OFF");

  display.setCursor(0, 46);
  if (isOnline()) {
    display.print("SERVER: TERVALIDASI");
  } else if (WiFi.status() == WL_CONNECTED) {
    display.printf("SERVER: TIDAK VALID %d", (int)lastHttpCode);
  } else {
    display.print("WIFI: MENYAMBUNG...");
  }

  display.setCursor(0, 56);
  switch (feeder) {
    case FEEDER_MAJU:   display.print("PAKAN: >> MAJU"); break;
    case FEEDER_MUNDUR: display.print("PAKAN: << MUNDUR"); break;
    case FEEDER_JEDA_A: display.print("PAKAN: JEDA DI A"); break;
    case FEEDER_JEDA_B: display.print("PAKAN: JEDA DI B"); break;
    default:            display.print("PAKAN: SIAGA"); break;
  }
  display.display();
}

// ============================== SETUP ==================================
void setup() {
  Serial.begin(115200);
  delay(300);

  // Relay dipastikan OFF sebelum apa pun
  pinMode(PIN_RELAY_KIPAS, OUTPUT);
  pinMode(PIN_RELAY_SPRINKLER, OUTPUT);
  pinMode(PIN_RELAY_LAMPU, OUTPUT);
  setRelay(PIN_RELAY_KIPAS, false);
  setRelay(PIN_RELAY_SPRINKLER, false);
  setRelay(PIN_RELAY_LAMPU, false);

  pinMode(PIN_MOTOR_IN1, OUTPUT);
  pinMode(PIN_MOTOR_IN2, OUTPUT);
  motorStop();

  pinMode(PIN_LED_BIRU, OUTPUT);
  digitalWrite(PIN_LED_BIRU, LOW);
  pinMode(PIN_LIMIT_A, INPUT_PULLUP);
  pinMode(PIN_LIMIT_B, INPUT_PULLUP);

  dhtSensor.setup(PIN_DHT, DHTesp::DHT22);

  if (display.begin(SSD1306_SWITCHCAPVCC, 0x3C)) {
    display.clearDisplay();
    display.setTextSize(1);
    display.setTextColor(SSD1306_WHITE);
    display.setCursor(15, 20);
    display.println("RANSUMKU IoT 4.0");
    display.setCursor(10, 35);
    display.println("SMKN 2 SURABAYA");
    display.display();
  }

  WiFi.mode(WIFI_STA);
  WiFi.setAutoReconnect(true);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
  Serial.printf("Menyambung WiFi %s ...\n", WIFI_SSID);

  xTaskCreatePinnedToCore(taskKomunikasi, "komunikasi", 8192, NULL, 1, NULL, 0);
}

// ============================== LOOP ===================================
void loop() {
  unsigned long now = millis();

  if (now - lastSensorRead >= 2000) {
    lastSensorRead = now;
    TempAndHumidity data = dhtSensor.getTempAndHumidity();
    if (!isnan(data.temperature) && !isnan(data.humidity)) {
      curSuhu = data.temperature;
      curRH = data.humidity;
      sensorSiap = true;
    } else {
      Serial.println("[DHT22] Gagal membaca sensor, cek kabel.");
    }
  }

  updateAktuator();
  updateFeeder();
  updateLedBiru();

  if (now - lastDisplay >= 300) {
    lastDisplay = now;
    renderDisplay();
  }
  delay(10);
}

