/**
 * =========================================================================
 * SIMULASI UTUH: SMART CLOSED-HOUSE LAYER FARM IoT (RANSUMKU)
 * Inovator: Aditya Ramadhani (SMKN 2 Surabaya)
 * 
 * INTEGRASI SISTEM LENGKAP:
 * 1. MIKROKLIMAT: Sensor DHT22 + Rumus THI Fisiologi Ayam Petelur
 * 2. SIRKULASI UDARA: Kipas Blower (Relay 1 - GPIO 12)
 * 3. RADIASI ATAP: Pompa Sprinkler Pendingin Seng (Relay 2 - GPIO 14)
 * 4. KERETA PAKAN 4WD: Driver L298N (GPIO 18, 19, 4, 16) + Katup Servo (GPIO 26)
 * 5. MONITORING CERDAS: Layar OLED Animasi Bergerak + Sakelar Batas A & B
 * 6. ANIMAL WELFARE: Audio Terapi Relaksasi Anti-Stres (GPIO 25)
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

// --- PIN ASSIGNMENT LENGKAP & AMAN ---
const int PIN_DHT             = 15; // Sensor DHT22
const int PIN_RELAY_KIPAS     = 12; // Relay Kipas Blower
const int PIN_RELAY_SPRINKLER = 14; // Relay Sprinkler Atap
const int PIN_SERVO_PAKAN     = 26; // Katup Corong Pakan di Mobil
const int PIN_BUZZER_SOUND    = 25; // Audio Anti-Stres

// Driver Motor L298N Mobil 4WD (Alat yang Anda punya)
const int PIN_IN1             = 18; // Motor Kiri Maju
const int PIN_IN2             = 19; // Motor Kiri Mundur
const int PIN_IN3             = 4;  // Motor Kanan Maju
const int PIN_IN4             = 16; // Motor Kanan Mundur

// Sakelar Batas Lorong Kandang
const int PIN_LIMIT_PANGKAL_A = 33; // Titik A (Pangkal)
const int PIN_LIMIT_UJUNG_B   = 32; // Titik B (Ujung)

DHTesp dhtSensor;
Servo servoPakan;

// --- STATE SISTEM & MODE PAKAN ---
enum FeederMode     { MODE_SHUTTLE, MODE_ROUND_TRIP };
enum FeederState    { FEEDER_IDLE, FEEDER_FORWARD, FEEDER_BACKWARD };
enum FeederPosition { POSISI_PANGKAL_A, POSISI_UJUNG_B };

FeederMode modePakan        = MODE_SHUTTLE;     // Default: Mode Shuttle (Ujung-ke-Ujung)
FeederState statePakan      = FEEDER_IDLE;
FeederPosition posisiKereta = POSISI_PANGKAL_A; // Start di Pangkal A

float posKeretaPixel = 14.0;
const float PIXEL_PANGKAL = 14.0;
const float PIXEL_UJUNG   = 106.0;

bool statusKipas     = false;
bool statusSprinkler = false;
bool statusSound     = false;

float curSuhu = 34.5;
float curRH   = 72.0;
float curTHI  = 82.0;

unsigned long lastSensorRead = 0;
unsigned long lastAnimUpdate = 0;
int fanAnimFrame = 0;

void setMotorL298N(int in1, int in2, int in3, int in4) {
  digitalWrite(PIN_IN1, in1);
  digitalWrite(PIN_IN2, in2);
  digitalWrite(PIN_IN3, in3);
  digitalWrite(PIN_IN4, in4);
}

void stopFeeder() {
  setMotorL298N(LOW, LOW, LOW, LOW);
  servoPakan.write(0); // Katup pakan menutup rapat
  statePakan = FEEDER_IDLE;
}

void jalanFeederMaju() {
  setMotorL298N(HIGH, LOW, HIGH, LOW); // 4 Roda Berputar Maju
  servoPakan.write(90);                // Katup corong membuka 90°
  statePakan = FEEDER_FORWARD;
  Serial.println(F(">> [KERETA 4WD]: Roda Meluncur MAJU (A -> B), Katup Ransum Membuka!"));
}

void jalanFeederMundur() {
  setMotorL298N(LOW, HIGH, LOW, HIGH); // 4 Roda Berputar Mundur
  servoPakan.write(90);                // Katup corong membuka 90°
  statePakan = FEEDER_BACKWARD;
  Serial.println(F(">> [KERETA 4WD]: Roda Meluncur MUNDUR (B -> A), Katup Ransum Membuka!"));
}

void picuPakan() {
  if (statePakan != FEEDER_IDLE) {
    Serial.println(F(">> [KERETA]: Sedang aktif berjalan!"));
    return;
  }

  if (modePakan == MODE_ROUND_TRIP) {
    posKeretaPixel = PIXEL_PANGKAL;
    jalanFeederMaju();
  } else {
    // Mode Shuttle: Bergantian sesuai posisi parkir saat ini
    if (posisiKereta == POSISI_PANGKAL_A) {
      jalanFeederMaju();
    } else {
      jalanFeederMundur();
    }
  }
}

// Menggambar Tampilan Dashboard & Animasi Bergerak di Layar OLED
void renderOLED() {
  display.clearDisplay();

  // 1. Baris Atas: Info Suhu, Kelembaban, THI & Status Kipas/Atap
  display.setTextSize(1);
  display.setTextColor(SSD1306_WHITE);
  display.setCursor(0, 0);
  display.printf("%.1fC %d%% THI:%.0f", curSuhu, (int)curRH, curTHI);

  // Animasi Ikon Kipas Berputar
  if (statusKipas) {
    const char fanChars[] = {'|', '/', '-', '\\'};
    display.setCursor(102, 0);
    display.printf("[%c]", fanChars[fanAnimFrame % 4]);
  } else {
    display.setCursor(102, 0);
    display.print("[ ]");
  }

  // Indikator Sprinkler Atap
  if (statusSprinkler) {
    display.setCursor(120, 0);
    display.print("*");
  }

  display.drawLine(0, 10, 127, 10, SSD1306_WHITE);

  // 2. Area Tengah: Rel Lorong Kandang & Kereta Pakan 4WD Berjalan
  display.setCursor(0, 22);
  display.print("A");
  display.setCursor(120, 22);
  display.print("B");

  display.drawLine(10, 25, 117, 25, SSD1306_WHITE);
  display.drawLine(10, 29, 117, 29, SSD1306_WHITE);
  for (int x = 12; x <= 115; x += 6) {
    display.drawLine(x, 25, x, 29, SSD1306_WHITE);
  }

  int xPos = (int)posKeretaPixel;
  display.fillRect(xPos - 4, 15, 10, 8, SSD1306_WHITE);
  display.drawRect(xPos - 5, 14, 12, 10, SSD1306_WHITE);
  display.fillCircle(xPos - 2, 24, 1, SSD1306_BLACK);
  display.fillCircle(xPos + 4, 24, 1, SSD1306_BLACK);

  // Butiran ransum jatuh saat membagikan pakan
  if (statePakan != FEEDER_IDLE) {
    display.drawPixel(xPos + 1, 31, SSD1306_WHITE);
    display.drawPixel(xPos - 1, 33, SSD1306_WHITE);
    display.drawPixel(xPos + 3, 33, SSD1306_WHITE);
  }

  // 3. Baris Bawah: Status Operasional
  display.drawLine(0, 38, 127, 38, SSD1306_WHITE);
  display.setCursor(0, 43);
  if (statePakan == FEEDER_FORWARD) {
    display.print(">> MAJU BAGI PAKAN");
  } else if (statePakan == FEEDER_BACKWARD) {
    display.print("<< MUNDUR BAGI PAKAN");
  } else {
    display.printf("PARKIR: TITIK %s", posisiKereta == POSISI_PANGKAL_A ? "A (PANGKAL)" : "B (UJUNG)");
  }

  display.setCursor(0, 54);
  display.printf("MODE: %s", modePakan == MODE_SHUTTLE ? "SHUTTLE (A<->B)" : "BOLAK-BALIK");

  display.display();
}

void setup() {
  Serial.begin(115200);
  delay(500);

  Serial.println(F("=========================================================="));
  Serial.println(F("  SMART CLOSED-HOUSE LAYER FARM IoT - RANSUMKU            "));
  Serial.println(F("  Inovator: Aditya Ramadhani (SMKN 2 Surabaya)            "));
  Serial.println(F("=========================================================="));

  pinMode(PIN_RELAY_KIPAS, OUTPUT);
  pinMode(PIN_RELAY_SPRINKLER, OUTPUT);
  pinMode(PIN_BUZZER_SOUND, OUTPUT);

  pinMode(PIN_IN1, OUTPUT);
  pinMode(PIN_IN2, OUTPUT);
  pinMode(PIN_IN3, OUTPUT);
  pinMode(PIN_IN4, OUTPUT);

  pinMode(PIN_LIMIT_PANGKAL_A, INPUT_PULLUP);
  pinMode(PIN_LIMIT_UJUNG_B, INPUT_PULLUP);

  digitalWrite(PIN_RELAY_KIPAS, LOW);
  digitalWrite(PIN_RELAY_SPRINKLER, LOW);
  digitalWrite(PIN_BUZZER_SOUND, LOW);
  stopFeeder();

  servoPakan.attach(PIN_SERVO_PAKAN);
  servoPakan.write(0);

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
    delay(1200);
  }

  Serial.println(F("[OK] Seluruh sistem siap & aman dalam kondisi STANDBY."));
}

void loop() {
  unsigned long now = millis();

  // 1. UPDATE ANIMASI LAYAR OLED (Tiap 40 ms)
  if (now - lastAnimUpdate >= 40) {
    lastAnimUpdate = now;
    fanAnimFrame++;

    if (statePakan == FEEDER_FORWARD) {
      posKeretaPixel += 0.8;
      if (posKeretaPixel >= PIXEL_UJUNG) {
        posKeretaPixel = PIXEL_UJUNG;
        if (modePakan == MODE_ROUND_TRIP) {
          stopFeeder();
          renderOLED();
          delay(800);
          jalanFeederMundur();
        } else {
          stopFeeder();
          posisiKereta = POSISI_UJUNG_B;
          Serial.println(F(">> [STATUS]: Kereta sampai & PARKIR DI TITIK B (Selesai)."));
        }
      }
    } else if (statePakan == FEEDER_BACKWARD) {
      posKeretaPixel -= 0.8;
      if (posKeretaPixel <= PIXEL_PANGKAL) {
        posKeretaPixel = PIXEL_PANGKAL;
        stopFeeder();
        posisiKereta = POSISI_PANGKAL_A;
        Serial.println(F(">> [STATUS]: Kereta sampai & PARKIR DI TITIK A (Selesai)."));
      }
    }

    renderOLED();
  }

  // 2. EVALUASI SENSOR SUHU & THI (Tiap 2 detik)
  if (now - lastSensorRead >= 2000) {
    lastSensorRead = now;

    TempAndHumidity data = dhtSensor.getTempAndHumidity();
    if (!isnan(data.temperature) && !isnan(data.humidity)) {
      curSuhu = data.temperature;
      curRH   = data.humidity;
      curTHI  = (1.8 * curSuhu + 32.0) - (0.55 - 0.0055 * curRH) * (1.8 * curSuhu - 26.0);

      // Kipas Blower Sirkulasi
      if (curSuhu >= 31.0 || curTHI >= 78.0) {
        if (!statusKipas) {
          statusKipas = true;
          digitalWrite(PIN_RELAY_KIPAS, HIGH);
          Serial.println(F("  -> [KIPAS]: OTOMATIS ON (Meredam Heat Stress)."));
        }
      } else if (curSuhu < 30.0 && curTHI < 76.0) {
        if (statusKipas) {
          statusKipas = false;
          digitalWrite(PIN_RELAY_KIPAS, LOW);
          Serial.println(F("  -> [KIPAS]: OTOMATIS OFF."));
        }
      }

      // Pompa Sprinkler Atap Seng
      if (curSuhu >= 33.0 || curTHI >= 80.0) {
        if (!statusSprinkler) {
          statusSprinkler = true;
          digitalWrite(PIN_RELAY_SPRINKLER, HIGH);
          Serial.println(F("  -> [SPRINKLER ATAP]: OTOMATIS MENYEMPROT SENG."));
        }
      } else if (curSuhu < 31.5 && curTHI < 78.0) {
        if (statusSprinkler) {
          statusSprinkler = false;
          digitalWrite(PIN_RELAY_SPRINKLER, LOW);
          Serial.println(F("  -> [SPRINKLER ATAP]: OTOMATIS BERHENTI."));
        }
      }
    }
  }

  // 3. DETEKSI SAKELAR BATAS MANUAL (Limit Switch A & B)
  int limitA = digitalRead(PIN_LIMIT_PANGKAL_A);
  int limitB = digitalRead(PIN_LIMIT_UJUNG_B);

  if (limitB == LOW && statePakan == FEEDER_FORWARD) {
    posKeretaPixel = PIXEL_UJUNG;
    if (modePakan == MODE_ROUND_TRIP) {
      stopFeeder();
      delay(800);
      jalanFeederMundur();
    } else {
      stopFeeder();
      posisiKereta = POSISI_UJUNG_B;
    }
  }

  if (limitA == LOW && statePakan == FEEDER_BACKWARD) {
    posKeretaPixel = PIXEL_PANGKAL;
    stopFeeder();
    posisiKereta = POSISI_PANGKAL_A;
  }

  // 4. PARSER PERINTAH SERIAL
  if (Serial.available() > 0) {
    String cmd = Serial.readStringUntil('\n');
    cmd.trim();
    cmd.toLowerCase();

    if (cmd == "pakan" || cmd == "beri_pakan") {
      picuPakan();
    } else if (cmd == "stop") {
      stopFeeder();
      Serial.println(F(">> [STOP]: Kereta pakan dihentikan."));
    } else if (cmd == "mode_shuttle") {
      modePakan = MODE_SHUTTLE;
      Serial.println(F(">> [MODE]: SHUTTLE (Ujung-ke-Ujung Bergantian)."));
    } else if (cmd == "mode_trip") {
      modePakan = MODE_ROUND_TRIP;
      Serial.println(F(">> [MODE]: BOLAK-BALIK (A -> B -> A)."));
    } else if (cmd == "sound_on") {
      statusSound = true;
      digitalWrite(PIN_BUZZER_SOUND, HIGH);
    } else if (cmd == "sound_off") {
      statusSound = false;
      digitalWrite(PIN_BUZZER_SOUND, LOW);
    }
  }
}
