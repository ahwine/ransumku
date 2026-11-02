/**
 * =========================================================================
 * SIMULASI WOKWI: SMART CLOSED-HOUSE LAYER FARM IoT (RANSUMKU)
 * Inovator: Aditya Ramadhani (SMKN 2 Surabaya)
 * Platform: RansumKu - Top 10 Finalis Lomba Inovasi Peternakan Nasional
 * 
 * FITUR AUTO-RUN LIVE DEMO:
 * 1. BEGITU KLIK PLAY ▶, SEMUA SISTEM OTOMATIS LANGSUNG AKTIF!
 *    - Motor Katrol Pakan berputar maju menarik wadah (A -> B).
 *    - Sampai di B, jeda 2 detik, lalu otomatis mundur (B -> A).
 *    - Kipas Sirkulasi Blower & Sprinkler Atap otomatis aktif meredam panas.
 * 2. Layar OLED Display SSD1306 menampilkan data Suhu, RH, THI, dan Status Alat.
 * 3. Menggunakan formula ilmiah biometeorologi unggas NRC / Zulovich & DeShazer (1990):
 *    THI = 0.8 * T + (RH / 100) * (T - 14.4) + 46.4
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

// --- PIN ASSIGNMENT ---
const int PIN_DHT               = 15; // Sensor DHT22 Suhu & Kelembaban
const int PIN_MOTOR_PAKAN       = 18; // Motor Penggerak Katrol Tali Pakan Langsung (PWM)
const int PIN_RELAY_KIPAS       = 12; // Relay Kipas Blower (GPIO 12)
const int PIN_RELAY_SPRINKLER   = 14; // Relay Sprinkler Atap Luar (GPIO 14)
const int PIN_BUZZER_SOUND      = 25; // Audio Anti-Stres (GPIO 25)
const int PIN_LIMIT_PANGKAL_A   = 33; // Sakelar Batas Pangkal Rel (Titik A)
const int PIN_LIMIT_UJUNG_B     = 32; // Sakelar Batas Ujung Rel (Titik B)

DHTesp dhtSensor;
Servo motorPenarikPakan;

// --- STATE SISTEM & SIKLUS DEMO OTOMATIS ---
enum FeederMode     { MODE_SHUTTLE, MODE_ROUND_TRIP };
enum FeederState    { FEEDER_IDLE, FEEDER_FORWARD, FEEDER_BACKWARD, FEEDER_PAUSE_END, FEEDER_PAUSE_START };
enum FeederPosition { POSISI_PANGKAL_A, POSISI_UJUNG_B };

FeederMode modePakan        = MODE_SHUTTLE;
FeederState statePakan      = FEEDER_FORWARD;   // Langsung AUTO-RUN Maju saat pertama start!
FeederPosition posisiKereta = POSISI_PANGKAL_A;

int motorAngle = 0;
int motorDir   = 1;
unsigned long waktuMulaiJalan = 0;
unsigned long waktuJeda = 0;
const unsigned long DURASI_JALAN_MS = 5000; // Motor jalan 5 detik dari A ke B
const unsigned long JEDA_PARKIR_MS  = 2000; // Jeda parkir 2 detik sebelum sesi berikutnya

bool statusKipas     = true; // Langsung aktif di awal demo
bool statusSprinkler = true; // Langsung aktif di awal demo
bool statusSound     = false;

float curSuhu = 33.5;
float curRH   = 70.0;
float curTHI  = 86.6; // Kondisi tropis siang hari

unsigned long lastSensorRead = 0;
unsigned long lastDisplayUpdate = 0;

void stopMotorPakan() {
  motorPenarikPakan.write(0); // Motor diam
}

void jalanFeederMaju() {
  statePakan = FEEDER_FORWARD;
  waktuMulaiJalan = millis();
  Serial.println(F(">> [AUTO-DEMO]: Motor katrol LANGSUNG BERPUTAR MAJU menarik wadah pakan (A -> B)..."));
}

void jalanFeederMundur() {
  statePakan = FEEDER_BACKWARD;
  waktuMulaiJalan = millis();
  Serial.println(F(">> [AUTO-DEMO]: Motor katrol LANGSUNG BERPUTAR MUNDUR menarik wadah pakan (B -> A)..."));
}

// Menampilkan Parameter Telemetri Suhu, Kelembaban, THI, dan Status Alat di Layar OLED
void renderDisplay() {
  display.clearDisplay();
  display.setTextColor(SSD1306_WHITE);

  // 1. Header Banner
  display.setTextSize(1);
  display.setCursor(8, 0);
  display.print("RANSUMKU - SMART FARM");
  display.drawLine(0, 10, 127, 10, SSD1306_WHITE);

  // 2. Data Sensor Suhu & Kelembaban
  display.setCursor(0, 14);
  display.printf("Suhu : %.1f C", curSuhu);

  display.setCursor(76, 14);
  display.printf("RH: %d%%", (int)curRH);

  // 3. Indeks Stres Panas Ayam (THI Standar Ilmiah Unggas)
  display.setCursor(0, 26);
  String labelTHI = "NYAMAN";
  if (curTHI >= 84.0)      labelTHI = "BAHAYA";
  else if (curTHI >= 78.0) labelTHI = "KRITIS";
  else if (curTHI >= 72.0) labelTHI = "WASPADA";

  display.printf("THI  : %.1f (%s)", curTHI, labelTHI.c_str());

  // 4. Status Aktuator Kipas Blower & Sprinkler Atap Luar
  display.setCursor(0, 38);
  display.printf("Kipas: %s", statusKipas ? "AKTIF" : "OFF");

  display.setCursor(68, 38);
  display.printf("Atap: %s", statusSprinkler ? "NYEMPROT" : "OFF");

  display.drawLine(0, 48, 127, 48, SSD1306_WHITE);

  // 5. Status Langsung Mesin Pakan
  display.setCursor(0, 53);
  if (statePakan == FEEDER_FORWARD) {
    display.print("PAKAN: >> MAJU (A->B)");
  } else if (statePakan == FEEDER_BACKWARD) {
    display.print("PAKAN: << MUNDUR (B->A)");
  } else if (statePakan == FEEDER_PAUSE_END) {
    display.print("PAKAN: PARKIR DI B (JEDA)");
  } else {
    display.print("PAKAN: PARKIR DI A (JEDA)");
  }

  display.display();
}

void setup() {
  Serial.begin(115200);
  delay(500);

  Serial.println(F("=========================================================="));
  Serial.println(F("  SMART CLOSED-HOUSE LAYER FARM IoT - RANSUMKU            "));
  Serial.println(F("  Inovator: Aditya Ramadhani (SMKN 2 Surabaya)            "));
  Serial.println(F("  Status  : Top 10 Finalis Lomba Peternakan Nasional UGM  "));
  Serial.println(F("  Mode    : AUTO-RUN LIVE DEMO (Closed-Loop Automation)   "));
  Serial.println(F("=========================================================="));

  pinMode(PIN_RELAY_KIPAS, OUTPUT);
  pinMode(PIN_RELAY_SPRINKLER, OUTPUT);
  pinMode(PIN_BUZZER_SOUND, OUTPUT);

  pinMode(PIN_LIMIT_PANGKAL_A, INPUT_PULLUP);
  pinMode(PIN_LIMIT_UJUNG_B, INPUT_PULLUP);

  // Langsung nyalakan aktuator awal sesuai suhu panas tropis
  digitalWrite(PIN_RELAY_KIPAS, HIGH);
  digitalWrite(PIN_RELAY_SPRINKLER, HIGH);
  digitalWrite(PIN_BUZZER_SOUND, LOW);

  // Inisialisasi Motor Fisik Langsung Penarik Tali Pakan
  motorPenarikPakan.attach(PIN_MOTOR_PAKAN);

  dhtSensor.setup(PIN_DHT, DHTesp::DHT22);

  // Inisialisasi Layar Display
  if (display.begin(SSD1306_SWITCHCAPVCC, 0x3C)) {
    display.clearDisplay();
    display.setTextSize(1);
    display.setTextColor(SSD1306_WHITE);
    display.setCursor(15, 20);
    display.println("RANSUMKU IoT 4.0");
    display.setCursor(10, 35);
    display.println("SMKN 2 SURABAYA");
    display.display();
    delay(1000);
  }

  // AUTO-START: Langsung jalan maju di detik awal!
  waktuMulaiJalan = millis();
  Serial.println(F("[AUTO-START]: Simulasi dimulai! Motor katrol pakan langsung meluncur jalan."));
  Serial.println(F("[AUTO-START]: Relay Kipas & Sprinkler Atap langsung ON meredam suhu kandang."));
}

void loop() {
  unsigned long now = millis();

  // 1. SIKLUS GERAK MOTOR PAKAN OTOMATIS (AUTO-RUN DEMO)
  // A. Saat sedang MAJU (A -> B)
  if (statePakan == FEEDER_FORWARD) {
    motorAngle += 14 * motorDir;
    if (motorAngle >= 170) { motorAngle = 170; motorDir = -1; }
    else if (motorAngle <= 10) { motorAngle = 10; motorDir = 1; }
    motorPenarikPakan.write(motorAngle);

    if (now - waktuMulaiJalan >= DURASI_JALAN_MS) {
      stopMotorPakan();
      posisiKereta = POSISI_UJUNG_B;
      statePakan = FEEDER_PAUSE_END;
      waktuJeda = now;
      Serial.println(F(">> [AUTO-DEMO]: Kereta sampai di Ujung B! Jeda 2 detik lalu balik arah..."));
    }
  }
  // B. Jeda di Ujung B (2 detik) lalu otomatis MUNDUR
  else if (statePakan == FEEDER_PAUSE_END) {
    stopMotorPakan();
    if (now - waktuJeda >= JEDA_PARKIR_MS) {
      jalanFeederMundur();
    }
  }
  // C. Saat sedang MUNDUR (B -> A)
  else if (statePakan == FEEDER_BACKWARD) {
    motorAngle -= 14 * motorDir;
    if (motorAngle <= 10) { motorAngle = 10; motorDir = -1; }
    else if (motorAngle >= 170) { motorAngle = 170; motorDir = 1; }
    motorPenarikPakan.write(motorAngle);

    if (now - waktuMulaiJalan >= DURASI_JALAN_MS) {
      stopMotorPakan();
      posisiKereta = POSISI_PANGKAL_A;
      statePakan = FEEDER_PAUSE_START;
      waktuJeda = now;
      Serial.println(F(">> [AUTO-DEMO]: Kereta sampai di Pangkal A! Jeda 2 detik lalu jalan lagi..."));
    }
  }
  // D. Jeda di Pangkal A (2 detik) lalu otomatis MAJU lagi (Looping Shuttle)
  else if (statePakan == FEEDER_PAUSE_START) {
    stopMotorPakan();
    if (now - waktuJeda >= JEDA_PARKIR_MS) {
      jalanFeederMaju();
    }
  }

  // 2. REFRESH LAYAR DISPLAY (Tiap 250 ms)
  if (now - lastDisplayUpdate >= 250) {
    lastDisplayUpdate = now;
    renderDisplay();
  }

  // 3. EVALUASI SENSOR SUHU & FORMULA THI ILMIAH (Tiap 2 detik)
  if (now - lastSensorRead >= 2000) {
    lastSensorRead = now;

    TempAndHumidity data = dhtSensor.getTempAndHumidity();
    if (!isnan(data.temperature) && !isnan(data.humidity)) {
      curSuhu = data.temperature;
      curRH   = data.humidity;
      
      // Formula Zulovich & DeShazer (1990) standar unggas internasional:
      curTHI  = (0.8 * curSuhu) + ((curRH / 100.0) * (curSuhu - 14.4)) + 46.4;

      // Logika Closed-Loop 1: Kipas Blower Sirkulasi
      if (curSuhu >= 31.0 || curTHI >= 78.0) {
        if (!statusKipas) {
          statusKipas = true;
          digitalWrite(PIN_RELAY_KIPAS, HIGH);
          Serial.println(F("  -> [KIPAS BLOWER]: OTOMATIS ON (Meredam Stres Panas)."));
        }
      } else if (curSuhu < 30.0 && curTHI < 76.0) {
        if (statusKipas) {
          statusKipas = false;
          digitalWrite(PIN_RELAY_KIPAS, LOW);
          Serial.println(F("  -> [KIPAS BLOWER]: OTOMATIS OFF (Suhu Kembali Aman)."));
        }
      }

      // Logika Closed-Loop 2: Pompa Sprinkler Atap Luar
      if (curSuhu >= 33.0 || curTHI >= 80.0) {
        if (!statusSprinkler) {
          statusSprinkler = true;
          digitalWrite(PIN_RELAY_SPRINKLER, HIGH);
          Serial.println(F("  -> [SPRINKLER ATAP]: OTOMATIS MENYEMPROT SENG LUAR."));
        }
      } else if (curSuhu < 31.5 && curTHI < 78.0) {
        if (statusSprinkler) {
          statusSprinkler = false;
          digitalWrite(PIN_RELAY_SPRINKLER, LOW);
          Serial.println(F("  -> [SPRINKLER ATAP]: OTOMATIS BERHENTI (Seng Atap Sudah Sejuk)."));
        }
      }
    }
  }

  // 4. DETEKSI SAKELAR BATAS MEKANIK (Limit Switch A & B)
  int limitA = digitalRead(PIN_LIMIT_PANGKAL_A);
  int limitB = digitalRead(PIN_LIMIT_UJUNG_B);

  if (limitB == LOW && statePakan == FEEDER_FORWARD) {
    stopMotorPakan();
    posisiKereta = POSISI_UJUNG_B;
    statePakan = FEEDER_PAUSE_END;
    waktuJeda = now;
    Serial.println(F(">> [LIMIT SWITCH B TERSENTUH]: Motor otomatis dihentikan di Ujung B."));
  }

  if (limitA == LOW && statePakan == FEEDER_BACKWARD) {
    stopMotorPakan();
    posisiKereta = POSISI_PANGKAL_A;
    statePakan = FEEDER_PAUSE_START;
    waktuJeda = now;
    Serial.println(F(">> [LIMIT SWITCH A TERSENTUH]: Motor otomatis dihentikan di Pangkal A."));
  }
}
