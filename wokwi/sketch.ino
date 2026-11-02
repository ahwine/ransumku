/**
 * =========================================================================
 * SIMULASI UTUH: SMART CLOSED-HOUSE LAYER FARM IoT (RANSUMKU)
 * Inovator: Aditya Ramadhani (SMKN 2 Surabaya)
 * 
 * ALAT PEMBERI PAKAN: LANGSUNG MENGGUNAKAN MOTOR FISIK (BUKAN LAMPU LED!)
 * 1. Motor Penarik Pakan (Servo/Katrol Tali): Lengan motor berputar nyata menarik kereta!
 * 2. Layar OLED 128x64: Menampilkan visual kereta meluncur di atas rel (A <--> B)!
 * 3. Kipas Sirkulasi: Otomatis ON saat suhu panas (>= 31°C atau THI >= 78.0)
 * 4. Sprinkler Pendingin Atap: Otomatis ON saat cuaca terik (>= 33°C)
 * 5. Dua Sakelar Batas Lorong: Titik A (Pangkal) & Titik B (Ujung)
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
const int PIN_DHT               = 15; // Sensor DHT22
const int PIN_MOTOR_PAKAN       = 18; // Motor Penggerak Katrol Tali Pakan (Servo PWM)
const int PIN_RELAY_KIPAS       = 12; // Relay Kipas Blower
const int PIN_RELAY_SPRINKLER   = 14; // Relay Sprinkler Atap
const int PIN_BUZZER_SOUND      = 25; // Audio Anti-Stres
const int PIN_LIMIT_PANGKAL_A   = 33; // Sakelar Batas Pangkal A
const int PIN_LIMIT_UJUNG_B     = 32; // Sakelar Batas Ujung B

DHTesp dhtSensor;
Servo motorPenarikPakan; // Motor Fisik Langsung Penarik Tali Pakan

// --- STATE SISTEM & MODE KERETA ---
enum FeederMode     { MODE_SHUTTLE, MODE_ROUND_TRIP };
enum FeederState    { FEEDER_IDLE, FEEDER_FORWARD, FEEDER_BACKWARD };
enum FeederPosition { POSISI_PANGKAL_A, POSISI_UJUNG_B };

FeederMode modePakan        = MODE_SHUTTLE;     // Default: Mode Shuttle (Ujung-ke-Ujung)
FeederState statePakan      = FEEDER_IDLE;
FeederPosition posisiKereta = POSISI_PANGKAL_A; // Start di Pangkal A

float posKeretaPixel = 14.0;
const float PIXEL_PANGKAL = 14.0;
const float PIXEL_UJUNG   = 106.0;

int motorAngle = 0;
int motorDir   = 1;

bool statusKipas     = false;
bool statusSprinkler = false;
bool statusSound     = false;

float curSuhu = 34.5;
float curRH   = 72.0;
float curTHI  = 82.0;

unsigned long lastSensorRead = 0;
unsigned long lastAnimUpdate = 0;
int fanAnimFrame = 0;

void stopMotorPakan() {
  motorPenarikPakan.write(0); // Motor berhenti
  statePakan = FEEDER_IDLE;
}

void jalanFeederMaju() {
  statePakan = FEEDER_FORWARD;
  Serial.println(F(">> [MOTOR PENARIK PAKAN]: AKTIF! Motor katrol berputar MAJU menarik tali pakan dari A ke B..."));
}

void jalanFeederMundur() {
  statePakan = FEEDER_BACKWARD;
  Serial.println(F(">> [MOTOR PENARIK PAKAN]: AKTIF! Motor katrol berputar MUNDUR menarik tali pakan dari B ke A..."));
}

void picuPakan() {
  if (statePakan != FEEDER_IDLE) {
    Serial.println(F(">> [MOTOR]: Sedang aktif berjalan!"));
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

  // 2. Area Tengah: Tali Katrol Rel Kandang & Kereta Pakan Bergerak
  display.setCursor(0, 22);
  display.print("A");
  display.setCursor(120, 22);
  display.print("B");

  // Garis Rel Pipa Besi & Tali Sling
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

  // Butiran ransum jatuh saat kereta membagikan pakan
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

  pinMode(PIN_LIMIT_PANGKAL_A, INPUT_PULLUP);
  pinMode(PIN_LIMIT_UJUNG_B, INPUT_PULLUP);

  digitalWrite(PIN_RELAY_KIPAS, LOW);
  digitalWrite(PIN_RELAY_SPRINKLER, LOW);
  digitalWrite(PIN_BUZZER_SOUND, LOW);

  // Inisialisasi Motor Fisik Langsung Penarik Tali Pakan
  motorPenarikPakan.attach(PIN_MOTOR_PAKAN);
  stopMotorPakan();

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

  Serial.println(F("[OK] Seluruh sistem siap! Alat pakan menggunakan MOTOR FISIK LANGSUNG."));
  Serial.println(F("[INFO] Ketik 'pakan' di Serial Monitor untuk mulai menjalankan motor pakan."));
}

void loop() {
  unsigned long now = millis();

  // 1. UPDATE ANIMASI & FISIKA MOTOR PENARIK PAKAN BERPUTAR (Tiap 40 ms)
  if (now - lastAnimUpdate >= 40) {
    lastAnimUpdate = now;
    fanAnimFrame++;

    // Jika sedang MAJU: Lengan motor berputar bolak-balik menarik tali sling ke depan
    if (statePakan == FEEDER_FORWARD) {
      posKeretaPixel += 0.8;
      
      // Putaran lengan motor fisik nyata
      motorAngle += 10 * motorDir;
      if (motorAngle >= 170) { motorAngle = 170; motorDir = -1; }
      else if (motorAngle <= 10) { motorAngle = 10; motorDir = 1; }
      motorPenarikPakan.write(motorAngle);

      if (posKeretaPixel >= PIXEL_UJUNG) {
        posKeretaPixel = PIXEL_UJUNG;
        if (modePakan == MODE_ROUND_TRIP) {
          stopMotorPakan();
          renderOLED();
          delay(800);
          jalanFeederMundur();
        } else {
          stopMotorPakan();
          posisiKereta = POSISI_UJUNG_B;
          Serial.println(F(">> [STATUS]: Kereta sampai & PARKIR DI TITIK B. Motor Berhenti."));
        }
      }
    } 
    // Jika sedang MUNDUR: Lengan motor berputar sebaliknya menarik tali kembali ke pangkal
    else if (statePakan == FEEDER_BACKWARD) {
      posKeretaPixel -= 0.8;

      motorAngle -= 10 * motorDir;
      if (motorAngle <= 10) { motorAngle = 10; motorDir = -1; }
      else if (motorAngle >= 170) { motorAngle = 170; motorDir = 1; }
      motorPenarikPakan.write(motorAngle);

      if (posKeretaPixel <= PIXEL_PANGKAL) {
        posKeretaPixel = PIXEL_PANGKAL;
        stopMotorPakan();
        posisiKereta = POSISI_PANGKAL_A;
        Serial.println(F(">> [STATUS]: Kereta sampai & PARKIR DI TITIK A (Docking). Motor Berhenti."));
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
          Serial.println(F("  -> [SPRINKLER ATAP]: OTOMATIS MENYEMPROT."));
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
      stopMotorPakan();
      delay(800);
      jalanFeederMundur();
    } else {
      stopMotorPakan();
      posisiKereta = POSISI_UJUNG_B;
    }
  }

  if (limitA == LOW && statePakan == FEEDER_BACKWARD) {
    posKeretaPixel = PIXEL_PANGKAL;
    stopMotorPakan();
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
