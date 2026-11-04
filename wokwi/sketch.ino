/**
 * =========================================================================
 * SIMULASI WOKWI: MAKET KANDANG STIK ES KRIM & IoT (RANSUMKU)
 * Inovator: Aditya Ramadhani (SMKN 2 Surabaya)
 * Platform: RansumKu - Lomba Inovasi Peternakan Nasional
 *
 * SELURUH KOMPONEN SINKRON 1:1 DENGAN MAKET FISIK & WEB RANSUMKU:
 * 1. Sakelar Utama ON/OFF (Titik 1 - GPIO 26): Memutus/menyalakan sistem.
 * 2. Sensor DHT22 (GPIO 15): Mengukur Suhu & Kelembaban (% RH) tanpa sensor bau.
 * 3. Layar OLED 0.96" I2C (GPIO 21, 22): Menampilkan Suhu, RH, THI, & Status Alat.
 * 4. Motor Katrol Kereta Pakan (GPIO 18) + Limit Switch A (33) & B (32).
 * 5. Relay Ch-1 Kipas Plafon (GPIO 12): Aktif saat Suhu >= 31C atau THI >= 78.
 * 6. Relay Ch-2 Pompa Sprinkler Luar Atap (GPIO 14): Aktif saat THI >= 80,
 *    LENGKAP Proteksi Sekam Kering (otomatis KUNCI MATI bila RH >= 75%!).
 * 7. Lampu LED Hangat Fotoperiode (GPIO 27): Pencahayaan kandang ayam petelur.
 * =========================================================================
 */

#include <Wire.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>
#include <ESP32Servo.h>
#include "DHTesp.h"

#define SCREEN_WIDTH 128
#define SCREEN_HEIGHT 64
Adafruit_SSD1306 display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, -1);

// --- PEMETAAN PIN ESP32 (SAMA PERSIS DENGAN MAKET FISIK) ---
const int PIN_DHT               = 15; // Sensor DHT22 Suhu & Kelembaban
const int PIN_MOTOR_PAKAN       = 18; // Simulasi Motor Katrol Pakan (L298N + Motor Kuning)
const int PIN_RELAY_KIPAS       = 12; // Relay Ch-1: Kipas Sirkulasi Plafon
const int PIN_RELAY_SPRINKLER   = 14; // Relay Ch-2: Pompa Sprinkler Luar Atap
const int PIN_LED_LAMPU         = 27; // Lampu LED Hangat Fotoperiode
const int PIN_SAKELAR_UTAMA     = 26; // Sakelar Utama ON/OFF di Titik 1
const int PIN_LIMIT_PANGKAL_A   = 33; // Micro Limit Switch Pangkal Rel (Titik A)
const int PIN_LIMIT_UJUNG_B     = 32; // Micro Limit Switch Ujung Rel (Titik B)

DHTesp dhtSensor;
Servo motorPenarikPakan;

enum FeederState { FEEDER_IDLE, FEEDER_FORWARD, FEEDER_BACKWARD, FEEDER_PAUSE_END, FEEDER_PAUSE_START };
FeederState statePakan = FEEDER_FORWARD;

int motorAngle = 90;
int motorDir   = 1;
unsigned long waktuMulaiJalan = 0;
unsigned long waktuJeda       = 0;
const unsigned long DURASI_JALAN_MS = 5000;
const unsigned long JEDA_PARKIR_MS  = 2000;

bool sistemAktif     = true;
bool statusKipas     = true;
bool statusSprinkler = true;
bool statusLampu     = true;
bool kunciLembabRH   = false;

float curSuhu = 33.5;
float curRH   = 68.0;
float curTHI  = 86.2;

unsigned long lastSensorRead    = 0;
unsigned long lastDisplayUpdate = 0;

void matikanSemuaAlat() {
  digitalWrite(PIN_RELAY_KIPAS, LOW);
  digitalWrite(PIN_RELAY_SPRINKLER, LOW);
  digitalWrite(PIN_LED_LAMPU, LOW);
  motorPenarikPakan.write(90);
}

void jalanFeederMaju() {
  statePakan = FEEDER_FORWARD;
  waktuMulaiJalan = millis();
  Serial.println(F(">> [PAKAN]: Motor katrol menarik kereta MAJU (Pangkal A -> Ujung B)..."));
}

void jalanFeederMundur() {
  statePakan = FEEDER_BACKWARD;
  waktuMulaiJalan = millis();
  Serial.println(F(">> [PAKAN]: Motor katrol menarik kereta MUNDUR (Ujung B -> Pangkal A)..."));
}

void renderDisplay() {
  display.clearDisplay();

  if (!sistemAktif) {
    // Saat Sakelar Utama di Titik 1 dimatikan (OFF), layar OLED ikut padam
    display.display();
    return;
  }

  display.setTextColor(SSD1306_WHITE);
  display.setTextSize(1);
  display.setCursor(8, 0);
  display.print("RANSUMKU - SMART FARM");
  display.drawLine(0, 10, 127, 10, SSD1306_WHITE);

  // Suhu & RH
  display.setCursor(0, 14);
  display.printf("Suhu:%.1fC", curSuhu);
  display.setCursor(70, 14);
  display.printf("RH:%d%%", (int)curRH);

  // Indeks THI Unggas (Zulovich & DeShazer, 1990)
  const char* labelTHI = "NYAMAN";
  if (curTHI >= 84.0)      labelTHI = "BAHAYA";
  else if (curTHI >= 78.0) labelTHI = "KRITIS";
  else if (curTHI >= 72.0) labelTHI = "WASPADA";

  display.setCursor(0, 25);
  display.printf("THI :%.1f (%s)", curTHI, labelTHI);

  // Status Kipas, Sprinkler Atap, & Lampu
  display.setCursor(0, 36);
  if (kunciLembabRH) {
    display.printf("Kps:%s Atp:LOCK-RH!", statusKipas ? "ON" : "OF");
  } else {
    display.printf("Kps:%s Atp:%s Lmp:%s",
                   statusKipas ? "ON" : "OF",
                   statusSprinkler ? "ON" : "OF",
                   statusLampu ? "ON" : "OF");
  }

  display.drawLine(0, 47, 127, 47, SSD1306_WHITE);

  // Status Kereta Pakan 2 Tingkat
  display.setCursor(0, 52);
  if (statePakan == FEEDER_FORWARD)       display.print("PAKAN: >> MAJU (A->B)");
  else if (statePakan == FEEDER_BACKWARD) display.print("PAKAN: << MUNDUR(B->A)");
  else if (statePakan == FEEDER_PAUSE_END) display.print("PAKAN: JEDA DI UJUNG B");
  else                                    display.print("PAKAN: JEDA PANGKAL A");

  display.display();
}

void setup() {
  Serial.begin(115200);
  delay(300);

  Serial.println(F("=========================================================="));
  Serial.println(F("  SIMULASI MAKET IoT KANDANG STIK ES KRIM - RANSUMKU      "));
  Serial.println(F("  Inovator: Aditya Ramadhani (SMKN 2 Surabaya)            "));
  Serial.println(F("=========================================================="));

  pinMode(PIN_RELAY_KIPAS, OUTPUT);
  pinMode(PIN_RELAY_SPRINKLER, OUTPUT);
  pinMode(PIN_LED_LAMPU, OUTPUT);

  pinMode(PIN_SAKELAR_UTAMA, INPUT_PULLUP);
  pinMode(PIN_LIMIT_PANGKAL_A, INPUT_PULLUP);
  pinMode(PIN_LIMIT_UJUNG_B, INPUT_PULLUP);

  motorPenarikPakan.attach(PIN_MOTOR_PAKAN);
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
    delay(800);
  }

  digitalWrite(PIN_RELAY_KIPAS, HIGH);
  digitalWrite(PIN_RELAY_SPRINKLER, HIGH);
  digitalWrite(PIN_LED_LAMPU, HIGH);
  waktuMulaiJalan = millis();
}

void loop() {
  unsigned long now = millis();

  // 0. CEK SAKELAR UTAMA ON/OFF DI TITIK 1
  bool sakelarOn = (digitalRead(PIN_SAKELAR_UTAMA) == LOW);
  if (!sakelarOn) {
    if (sistemAktif) {
      sistemAktif = false;
      matikanSemuaAlat();
      renderDisplay();
      Serial.println(F("[SAKELAR UTAMA OFF]: Seluruh daya maket diputus dari Titik 1."));
    }
    delay(50);
    return;
  } else if (!sistemAktif) {
    sistemAktif = true;
    digitalWrite(PIN_LED_LAMPU, statusLampu ? HIGH : LOW);
    jalanFeederMaju();
    Serial.println(F("[SAKELAR UTAMA ON]: Daya maket aktif kembali!"));
  }

  // 1. SIKLUS MOTOR KATROL KERETA PAKAN 2 TINGKAT
  if (statePakan == FEEDER_FORWARD) {
    motorAngle += 14 * motorDir;
    if (motorAngle >= 170) { motorAngle = 170; motorDir = -1; }
    else if (motorAngle <= 10) { motorAngle = 10; motorDir = 1; }
    motorPenarikPakan.write(motorAngle);

    if (now - waktuMulaiJalan >= DURASI_JALAN_MS) {
      statePakan = FEEDER_PAUSE_END;
      waktuJeda = now;
      Serial.println(F(">> [PAKAN]: Sampai di Ujung B, jeda 2 detik..."));
    }
  } else if (statePakan == FEEDER_PAUSE_END) {
    if (now - waktuJeda >= JEDA_PARKIR_MS) {
      jalanFeederMundur();
    }
  } else if (statePakan == FEEDER_BACKWARD) {
    motorAngle -= 14 * motorDir;
    if (motorAngle <= 10) { motorAngle = 10; motorDir = -1; }
    else if (motorAngle >= 170) { motorAngle = 170; motorDir = 1; }
    motorPenarikPakan.write(motorAngle);

    if (now - waktuMulaiJalan >= DURASI_JALAN_MS) {
      statePakan = FEEDER_PAUSE_START;
      waktuJeda = now;
      Serial.println(F(">> [PAKAN]: Kembali di Pangkal A, jeda 2 detik..."));
    }
  } else if (statePakan == FEEDER_PAUSE_START) {
    if (now - waktuJeda >= JEDA_PARKIR_MS) {
      jalanFeederMaju();
    }
  }

  // 2. BACA SENSOR DHT22 & EVALUASI OTOMATISASI THI + PROTEKSI RH >= 75%
  if (now - lastSensorRead >= 1500) {
    lastSensorRead = now;
    TempAndHumidity data = dhtSensor.getTempAndHumidity();
    if (!isnan(data.temperature) && !isnan(data.humidity)) {
      curSuhu = data.temperature;
      curRH   = data.humidity;
      curTHI  = (0.8 * curSuhu) + ((curRH / 100.0) * (curSuhu - 14.4)) + 46.4;

      // A. Kipas Sirkulasi Plafon (Nyala jika Suhu >= 31C atau THI >= 78)
      statusKipas = (curSuhu >= 31.0 || curTHI >= 78.0);
      digitalWrite(PIN_RELAY_KIPAS, statusKipas ? HIGH : LOW);

      // B. Pompa Sprinkler Luar Atap + Safety Interlock RH >= 75% (Cegah Kotoran Lembap/Bau!)
      if (curRH >= 75.0) {
        statusSprinkler = false;
        kunciLembabRH   = true;
      } else {
        kunciLembabRH   = false;
        statusSprinkler = (curTHI >= 80.0);
      }
      digitalWrite(PIN_RELAY_SPRINKLER, statusSprinkler ? HIGH : LOW);
      digitalWrite(PIN_LED_LAMPU, statusLampu ? HIGH : LOW);
    }
  }

  // 3. DETEKSI MICRO LIMIT SWITCH PANGKAL A & UJUNG B
  if (digitalRead(PIN_LIMIT_UJUNG_B) == LOW && statePakan == FEEDER_FORWARD) {
    statePakan = FEEDER_PAUSE_END;
    waktuJeda = now;
    Serial.println(F(">> [LIMIT SWITCH B TERSENTUH]: Kereta berhenti aman di Ujung B!"));
  }
  if (digitalRead(PIN_LIMIT_PANGKAL_A) == LOW && statePakan == FEEDER_BACKWARD) {
    statePakan = FEEDER_PAUSE_START;
    waktuJeda = now;
    Serial.println(F(">> [LIMIT SWITCH A TERSENTUH]: Kereta berhenti aman di Pangkal A!"));
  }

  // 4. UPDATE LAYAR OLED TIAP 250 MS
  if (now - lastDisplayUpdate >= 250) {
    lastDisplayUpdate = now;
    renderDisplay();
  }
}
