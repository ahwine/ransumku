/**
 * =========================================================================
 * SIMULASI UTUH: SMART CLOSED-HOUSE LAYER FARM IoT (RANSUMKU)
 * Inovator: Aditya Ramadhani (SMKN 2 Surabaya)
 * 
 * 1. LAYAR DISPLAY OLED (LAYAR LED):
 *    Murni khusus menampilkan parameter: Suhu, Kelembaban, THI, dan Status Alat.
 * 2. ALAT PEMBERI PAKAN:
 *    Beroperasi langsung secara fisik menggunakan MOTOR KATROL PENARIK PAKAN!
 * 3. KIPAS SIRKULASI: Otomatis ON saat suhu >= 31°C atau THI >= 78.0
 * 4. SPRINKLER ATAP: Otomatis ON saat suhu >= 33°C
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
const int PIN_RELAY_KIPAS       = 12; // Relay Kipas Blower
const int PIN_RELAY_SPRINKLER   = 14; // Relay Sprinkler Atap
const int PIN_BUZZER_SOUND      = 25; // Audio Anti-Stres
const int PIN_LIMIT_PANGKAL_A   = 33; // Sakelar Batas Pangkal (Titik A)
const int PIN_LIMIT_UJUNG_B     = 32; // Sakelar Batas Ujung (Titik B)

DHTesp dhtSensor;
Servo motorPenarikPakan; // Motor Fisik Langsung Penarik Tali Pakan

// --- STATE SISTEM & MODE PAKAN ---
enum FeederMode     { MODE_SHUTTLE, MODE_ROUND_TRIP };
enum FeederState    { FEEDER_IDLE, FEEDER_FORWARD, FEEDER_BACKWARD };
enum FeederPosition { POSISI_PANGKAL_A, POSISI_UJUNG_B };

FeederMode modePakan        = MODE_SHUTTLE;     // Default: Mode Shuttle (Ujung-ke-Ujung)
FeederState statePakan      = FEEDER_IDLE;
FeederPosition posisiKereta = POSISI_PANGKAL_A; // Start di Pangkal A

int motorAngle = 0;
int motorDir   = 1;
unsigned long waktuMulaiJalan = 0;
const unsigned long DURASI_JALAN_MS = 5000; // Motor jalan 5 detik dari A ke B

bool statusKipas     = false;
bool statusSprinkler = false;
bool statusSound     = false;

float curSuhu = 34.5;
float curRH   = 72.0;
float curTHI  = 82.0;

unsigned long lastSensorRead = 0;
unsigned long lastDisplayUpdate = 0;

void stopMotorPakan() {
  motorPenarikPakan.write(0); // Motor berhenti
  statePakan = FEEDER_IDLE;
}

void jalanFeederMaju() {
  statePakan = FEEDER_FORWARD;
  waktuMulaiJalan = millis();
  Serial.println(F(">> [MOTOR PAKAN]: AKTIF! Motor katrol berputar MAJU menarik wadah pakan dari A ke B..."));
}

void jalanFeederMundur() {
  statePakan = FEEDER_BACKWARD;
  waktuMulaiJalan = millis();
  Serial.println(F(">> [MOTOR PAKAN]: AKTIF! Motor katrol berputar MUNDUR menarik wadah pakan dari B ke A..."));
}

void picuPakan() {
  if (statePakan != FEEDER_IDLE) {
    Serial.println(F(">> [MOTOR]: Sedang aktif berjalan!"));
    return;
  }

  if (modePakan == MODE_ROUND_TRIP) {
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

// Menampilkan Parameter Telemetri Suhu, Kelembaban, THI, dan Status Alat di Layar Display
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

  // 3. Indeks Stres Panas Ayam (THI)
  display.setCursor(0, 26);
  String labelTHI = "AMAN";
  if (curTHI >= 84.0)      labelTHI = "BAHAYA";
  else if (curTHI >= 80.0) labelTHI = "STRES BERAT";
  else if (curTHI >= 78.0) labelTHI = "WASPADA";

  display.printf("THI  : %.1f (%s)", curTHI, labelTHI.c_str());

  // 4. Status Aktuator Kipas & Sprinkler Atap
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
  } else {
    display.printf("PAKAN: PARKIR TITIK %s", posisiKereta == POSISI_PANGKAL_A ? "A" : "B");
  }

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

  pinMode(PIN_LIMIT_PANGKAL_A, INPUT_PULLUP);
  pinMode(PIN_LIMIT_UJUNG_B, INPUT_PULLUP);

  digitalWrite(PIN_RELAY_KIPAS, LOW);
  digitalWrite(PIN_RELAY_SPRINKLER, LOW);
  digitalWrite(PIN_BUZZER_SOUND, LOW);

  // Inisialisasi Motor Fisik Langsung Penarik Tali Pakan
  motorPenarikPakan.attach(PIN_MOTOR_PAKAN);
  stopMotorPakan();

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
    delay(1200);
  }

  Serial.println(F("[OK] Seluruh sistem siap!"));
  Serial.println(F("[INFO] Layar Display murni menampilkan Suhu, RH, THI, dan Status Alat."));
  Serial.println(F("[INFO] Alat pakan langsung digerakkan oleh MOTOR KATROL FISIK."));
}

void loop() {
  unsigned long now = millis();

  // 1. GERAKAN MOTOR FISIK PENARIK PAKAN (Berputar Langsung Saat Aktif)
  if (statePakan != FEEDER_IDLE) {
    // Putaran lengan motor fisik nyata bolak-balik selama menarik tali pakan
    motorAngle += 12 * motorDir;
    if (motorAngle >= 170) { motorAngle = 170; motorDir = -1; }
    else if (motorAngle <= 10) { motorAngle = 10; motorDir = 1; }
    motorPenarikPakan.write(motorAngle);

    // Deteksi Waktu Tempuh Lorong (5 Detik)
    if (now - waktuMulaiJalan >= DURASI_JALAN_MS) {
      if (statePakan == FEEDER_FORWARD) {
        if (modePakan == MODE_ROUND_TRIP) {
          stopMotorPakan();
          renderDisplay();
          delay(800);
          jalanFeederMundur();
        } else {
          stopMotorPakan();
          posisiKereta = POSISI_UJUNG_B;
          Serial.println(F(">> [STATUS]: Wadah pakan sampai di Titik B. Motor berhenti parkir."));
        }
      } else if (statePakan == FEEDER_BACKWARD) {
        stopMotorPakan();
        posisiKereta = POSISI_PANGKAL_A;
        Serial.println(F(">> [STATUS]: Wadah pakan sampai di Titik A. Motor berhenti parkir."));
      }
    }
  }

  // 2. REFRESH LAYAR DISPLAY (Tiap 300 ms)
  if (now - lastDisplayUpdate >= 300) {
    lastDisplayUpdate = now;
    renderDisplay();
  }

  // 3. EVALUASI SENSOR SUHU & THI (Tiap 2 detik)
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
          Serial.println(F("  -> [KIPAS]: OTOMATIS ON (Meredam Panas)."));
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

  // 4. DETEKSI SAKELAR BATAS MANUAL (Limit Switch A & B jika ditekan)
  int limitA = digitalRead(PIN_LIMIT_PANGKAL_A);
  int limitB = digitalRead(PIN_LIMIT_UJUNG_B);

  if (limitB == LOW && statePakan == FEEDER_FORWARD) {
    if (modePakan == MODE_ROUND_TRIP) {
      stopMotorPakan();
      delay(800);
      jalanFeederMundur();
    } else {
      stopMotorPakan();
      posisiKereta = POSISI_UJUNG_B;
    }
  }

  if (limitA == LOW && statePakan == FEEDER_BACKWARD) {
    stopMotorPakan();
    posisiKereta = POSISI_PANGKAL_A;
  }

  // 5. PARSER PERINTAH SERIAL
  if (Serial.available() > 0) {
    String cmd = Serial.readStringUntil('\n');
    cmd.trim();
    cmd.toLowerCase();

    if (cmd == "pakan" || cmd == "beri_pakan") {
      picuPakan();
    } else if (cmd == "stop") {
      stopMotorPakan();
      Serial.println(F(">> [STOP]: Motor pakan dihentikan seketika."));
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
