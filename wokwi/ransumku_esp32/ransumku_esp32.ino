/**
 * =========================================================================
 * RANSUMKU - FIRMWARE RESMI ESP32 (SINKRON CLOUD https://ransumku.ditzy.biz.id)
 * Inovator : Aditya Ramadhani (SMKN 2 Surabaya)
 *
 * DAFTAR PIN AKTIF (3 AKTUATOR UTAMA + SENSOR + LIMIT SWITCH + OLED):
 *   - Daya ESP32         : OUT+ LM2596 (5.0V) -> VIN (Atas #1) | OUT- -> GND (Atas #2)
 *   - Sensor DHT22       : 3V3 (Bawah #1), GND (Bawah #2), DATA -> Pin D15 (Bawah #3)
 *   - Layar OLED I2C     : SDA -> Pin D21 (Bawah #11) | SCL -> Pin D22 (Bawah #14)
 *   - Daya L298N 5.0V    : OUT+ LM2596 -> Sekrup "12V" & Jembatan ke "+5V" (Topi 5V-EN pindah ke ENA)
 *   - Motor Kereta Pakan : Pin D18 (Bawah #9) -> IN1 | Pin D19 (Bawah #10) -> IN2 | OUT1 & OUT2
 *   - Limit Switch A     : Pin D33 (Atas #9)  -> Kaki NO Switch Merah (COM ke GND)
 *   - Limit Switch B     : Pin D32 (Atas #10) -> Kaki NO Switch Hitam (COM ke GND)
 *   - Kontrol Relay      : Pin D26 (Atas #7)  -> IN1 (Kipas 12V) | Pin D14 (Atas #5) -> IN2 (Pompa 5V)
 *
 * FITUR BARU:
 *   1. Terhubung langsung ke Domain Cloud Live: https://ransumku.ditzy.biz.id
 *      (otomatis fallback ke server lokal laptop bila hotspot sedang tanpa internet).
 *   2. Animasi Pembuka (Boot Splash Animation) LCD OLED 128x64 berbasis logo.png resmi RansumKu!
 * =========================================================================
 */

#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <HTTPClient.h>
#include <Wire.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>
#include "DHTesp.h"

// ======================== 1. KONFIGURASI JARINGAN ========================
const char* WIFI_SSID     = "RansumKu_IoT";
const char* WIFI_PASSWORD = "12345678";

// Server Cloud Utama (cPanel Live) & Server Lokal Cadangan
const char* SERVER_URL    = "https://ransumku.ditzy.biz.id";
const int   FARM_ID       = 1;
const char* IOT_TOKEN     = "";              // Kosongkan bila kandang tidak memakai token

const unsigned long INTERVAL_KIRIM_MS = 1200;  // Sinkron cepat ke Web setiap 1.2 detik
const unsigned long VALID_TIMEOUT_MS  = 10000; // Batas waktu dianggap terputus bila server tidak merespons

// Logika Polaritas Relay CW-021 menyesuaikan posisi kabel fisik Anda (#1 & #4):
const bool RELAY_ACTIVE_LOW = false;

// =================== 2. PINOUT HARDWARE ESP32 ============================
const int PIN_DHT             = 15;  // Sensor Suhu & Kelembaban DHT22 (ESP32 Bawah Pin #3)
const int PIN_OLED_SDA        = 21;  // SDA OLED 0.96"                 (ESP32 Bawah Pin #11)
const int PIN_OLED_SCL        = 22;  // SCL OLED 0.96"                 (ESP32 Bawah Pin #14)
const int PIN_MOTOR_IN1       = 18;  // IN1 L298N (Motor Kereta Pakan) (ESP32 Bawah Pin #9)
const int PIN_MOTOR_IN2       = 19;  // IN2 L298N (Motor Kereta Pakan) (ESP32 Bawah Pin #10)
const int PIN_LIMIT_A         = 33;  // Limit Switch Merah Pangkal A   (ESP32 Atas Pin #9)
const int PIN_LIMIT_B         = 32;  // Limit Switch Hitam Ujung B     (ESP32 Atas Pin #10)
const int PIN_RELAY_KIPAS     = 26;  // IN1 Relay (Kipas Angin 12V)    (ESP32 Atas Pin #7)
const int PIN_RELAY_SPRINKLER = 14;  // IN2 Relay (Pompa Misting 5V)   (ESP32 Atas Pin #5)
const int PIN_LED_BIRU        = 2;   // Indikator LED Biru Onboard     (ESP32 Bawah Pin #4)

// Layar OLED SSD1306 128x64 I2C
#define SCREEN_WIDTH 128
#define SCREEN_HEIGHT 64
Adafruit_SSD1306 display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, -1);
DHTesp dhtSensor;

// ============== 3. BITMAP LOGO.PNG RESMI RANSUMKU (PROGMEM) ==============
// Bitmap 56x56 piksel hasil konversi langsung dari public/images/logo.png
const unsigned char LOGO_RANSUMKU_56[] PROGMEM = {
  0x00, 0x00, 0x00, 0x00, 0x01, 0x80, 0x00, 0x00, 0x00, 0x00, 0x00, 0x03, 0xF0, 0x00, 0x00, 0x00,
  0x00, 0x00, 0x03, 0xF8, 0x00, 0x00, 0x00, 0x00, 0x00, 0x78, 0xFE, 0x00, 0x00, 0x00, 0x00, 0x00,
  0x78, 0x7F, 0x00, 0x00, 0x00, 0x01, 0xFE, 0x00, 0x1F, 0x80, 0x00, 0x00, 0xEF, 0xFF, 0x00, 0x07,
  0x80, 0x00, 0x03, 0x9F, 0xE7, 0x00, 0x01, 0xC0, 0x00, 0x0E, 0x3F, 0x87, 0x80, 0x7C, 0xC0, 0x00,
  0x18, 0x18, 0xEF, 0xF3, 0xFF, 0x00, 0x00, 0x30, 0x03, 0xFF, 0xE7, 0xFF, 0x80, 0x00, 0x60, 0x00,
  0x3F, 0xCF, 0xE1, 0x80, 0x01, 0xC0, 0x00, 0xE7, 0x9F, 0xE0, 0xF0, 0x01, 0xC0, 0x00, 0xE0, 0x3F,
  0xF1, 0xF8, 0x03, 0xE0, 0x00, 0xE0, 0x7F, 0xFF, 0xFC, 0x07, 0xE0, 0x00, 0xE0, 0x7F, 0xFF, 0x78,
  0x07, 0xF0, 0x1C, 0xE0, 0xFF, 0xFE, 0x00, 0x0D, 0xF0, 0x1C, 0xE1, 0xFF, 0xFC, 0x00, 0x0C, 0xF0,
  0x1C, 0xC1, 0xFF, 0xFC, 0x00, 0x1E, 0x71, 0x9C, 0x03, 0xFF, 0xFC, 0x70, 0x1F, 0xB3, 0x98, 0x07,
  0xFF, 0xFC, 0xE0, 0x17, 0xD7, 0x80, 0x1F, 0xFF, 0xFD, 0x80, 0x37, 0xC7, 0x80, 0x7F, 0xFF, 0xFE,
  0x00, 0x23, 0xEF, 0x03, 0xFF, 0xFF, 0xFF, 0x00, 0x21, 0xEF, 0xBC, 0x07, 0xFF, 0xFF, 0x80, 0x60,
  0xED, 0xFE, 0x00, 0xFF, 0xFF, 0xC0, 0x70, 0x77, 0xCC, 0x08, 0x3F, 0xE7, 0xE0, 0x7C, 0x37, 0x00,
  0xFF, 0x9F, 0xF1, 0xF0, 0x7F, 0x82, 0x03, 0xFF, 0xE7, 0xD8, 0xF0, 0x7F, 0xFC, 0x07, 0xFF, 0xF3,
  0xCF, 0x78, 0x5D, 0xFF, 0x8D, 0xFF, 0xF9, 0xC7, 0xBC, 0x7F, 0xFF, 0xE8, 0xFF, 0xEC, 0xC7, 0xFC,
  0x68, 0xFF, 0xF8, 0x7F, 0xF6, 0xC3, 0xFE, 0x68, 0x3F, 0xFC, 0x3F, 0xFF, 0x03, 0xFE, 0x64, 0x0F,
  0xFE, 0x3F, 0xFF, 0x01, 0xBF, 0x22, 0x03, 0xFF, 0x1F, 0xFF, 0x81, 0xBF, 0x21, 0x00, 0x7D, 0x8F,
  0xFF, 0xC1, 0xFF, 0x30, 0x80, 0x1E, 0xC7, 0xFF, 0xC1, 0xDF, 0x30, 0x40, 0x0F, 0x43, 0xFF, 0xE1,
  0xDF, 0x10, 0x00, 0x03, 0xA1, 0xFF, 0x93, 0xDF, 0x18, 0x00, 0x00, 0xE0, 0xFF, 0x93, 0x9F, 0x08,
  0x00, 0x08, 0x10, 0x7E, 0x17, 0x9E, 0x0C, 0x0F, 0xC4, 0x00, 0x00, 0x1F, 0xBE, 0x04, 0x1F, 0xE2,
  0x08, 0x00, 0x3F, 0x3E, 0x06, 0x1F, 0xE1, 0x08, 0x00, 0x1E, 0x3C, 0x03, 0x00, 0xF0, 0x08, 0x00,
  0x3C, 0x78, 0x01, 0x80, 0x10, 0x8C, 0x00, 0x7C, 0x70, 0x00, 0x80, 0x08, 0x0C, 0x03, 0xC0, 0x60,
  0x00, 0x60, 0x00, 0x4F, 0xBC, 0x00, 0xC0, 0x00, 0x30, 0x00, 0x0F, 0xF0, 0x01, 0x80, 0x00, 0x10,
  0x00, 0x0F, 0x80, 0x03, 0x00, 0x00, 0x00, 0x00, 0x0E, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x18,
  0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x10, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00,
  0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00
};

// Bitmap 64x64 piksel hasil konversi langsung dari public/images/logo.png
const unsigned char LOGO_RANSUMKU_64[] PROGMEM = {
  0x00, 0x00, 0x00, 0x00, 0x00, 0x0E, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x1F, 0xC0, 0x00,
  0x00, 0x00, 0x00, 0x00, 0x00, 0x1F, 0xE0, 0x00, 0x00, 0x00, 0x00, 0x00, 0x03, 0x8F, 0xF8, 0x00,
  0x00, 0x00, 0x00, 0x00, 0x07, 0xE3, 0xFC, 0x00, 0x00, 0x00, 0x00, 0x00, 0x03, 0xC0, 0xFE, 0x00,
  0x00, 0x00, 0x00, 0x3F, 0xF0, 0x00, 0x3F, 0x00, 0x00, 0x00, 0x39, 0xFF, 0xF0, 0x00, 0x0F, 0x80,
  0x00, 0x00, 0xE7, 0xFE, 0x78, 0x00, 0x03, 0x80, 0x00, 0x03, 0x8F, 0xF8, 0x7C, 0x01, 0xF1, 0x80,
  0x00, 0x07, 0x0F, 0x86, 0x7F, 0x0F, 0xFC, 0x00, 0x00, 0x0C, 0x00, 0x3F, 0xFF, 0x9F, 0xFE, 0x00,
  0x00, 0x18, 0x00, 0x3F, 0xFF, 0x3F, 0xE3, 0x00, 0x00, 0xF0, 0x00, 0x06, 0xFE, 0x7F, 0x83, 0x80,
  0x00, 0xE0, 0x00, 0x1E, 0x7C, 0xFF, 0xC3, 0xF0, 0x00, 0xE0, 0x00, 0x1E, 0x01, 0xFF, 0xE3, 0xF8,
  0x01, 0xF0, 0x00, 0x1E, 0x03, 0xFF, 0xFF, 0xFC, 0x03, 0xF8, 0x00, 0x1E, 0x07, 0xFF, 0xFE, 0xF8,
  0x03, 0xF8, 0x03, 0xDE, 0x07, 0xFF, 0xFC, 0x40, 0x06, 0xFC, 0x03, 0xDE, 0x0F, 0xFF, 0xF8, 0x00,
  0x0C, 0xFC, 0x03, 0xDE, 0x1F, 0xFF, 0xF0, 0x00, 0x0E, 0x7C, 0x23, 0xD8, 0x1F, 0xFF, 0xF0, 0x20,
  0x0F, 0xBC, 0x63, 0x80, 0x3F, 0xFF, 0xF0, 0xE0, 0x1F, 0xD8, 0xE2, 0x00, 0x7F, 0xFF, 0xF1, 0xE0,
  0x13, 0xE9, 0xE0, 0x01, 0xFF, 0xFF, 0xFB, 0x00, 0x33, 0xF3, 0xE0, 0x07, 0xFF, 0xFF, 0xF9, 0x00,
  0x33, 0xF3, 0xE0, 0x7F, 0xFF, 0xFF, 0xFC, 0x00, 0x31, 0xF3, 0xC3, 0x01, 0xFF, 0xFF, 0xFE, 0x00,
  0x20, 0xFB, 0x3F, 0x80, 0x1F, 0xFF, 0xFF, 0x00, 0x20, 0x7B, 0x7F, 0xC0, 0x07, 0xFF, 0xFF, 0x80,
  0x78, 0x3B, 0xF0, 0x01, 0xC1, 0xFF, 0xCF, 0xC0, 0x7E, 0x0C, 0xC0, 0x17, 0xF8, 0xFF, 0xE7, 0xE0,
  0x7F, 0xC5, 0x80, 0x7F, 0xFE, 0x3F, 0x71, 0xF0, 0x7F, 0xFC, 0x00, 0xFF, 0xFF, 0x9F, 0x38, 0xF8,
  0x7D, 0xFF, 0xC0, 0xBF, 0xFF, 0xCF, 0x1F, 0x78, 0x7F, 0xFF, 0xF9, 0x9F, 0xFF, 0x67, 0x1F, 0xBC,
  0x6D, 0xFF, 0xFE, 0x0F, 0xFF, 0x33, 0x0F, 0xFC, 0x6C, 0x3F, 0xFF, 0x87, 0xFF, 0x99, 0x07, 0xFE,
  0x64, 0x07, 0xFF, 0x87, 0xFF, 0xF8, 0x07, 0xFE, 0x22, 0x03, 0xFF, 0xC3, 0xFF, 0xFC, 0x07, 0x7F,
  0x23, 0x00, 0xFE, 0xF1, 0xFF, 0xFE, 0x03, 0x7F, 0x31, 0x80, 0x1F, 0xB0, 0xFF, 0xFE, 0x03, 0xBF,
  0x30, 0xC0, 0x07, 0xD8, 0x7F, 0xFF, 0x8B, 0xBF, 0x10, 0x20, 0x03, 0xEC, 0x7F, 0xFF, 0x83, 0xBF,
  0x18, 0x10, 0x00, 0xF4, 0x3F, 0xFE, 0x17, 0xBF, 0x18, 0x00, 0x08, 0x3A, 0x1F, 0xFE, 0x47, 0x3F,
  0x08, 0x00, 0x00, 0x0E, 0x07, 0xFC, 0x4F, 0x3F, 0x0C, 0x00, 0x01, 0x01, 0x03, 0xF8, 0x5F, 0x3E,
  0x04, 0x03, 0xF0, 0x81, 0x00, 0x00, 0x5E, 0x3E, 0x06, 0x0F, 0xFC, 0x40, 0x00, 0x00, 0xFE, 0x7C,
  0x03, 0x0F, 0xFC, 0x20, 0x80, 0x00, 0x7C, 0x7C, 0x01, 0x00, 0x7C, 0x00, 0x80, 0x00, 0x78, 0x78,
  0x01, 0x80, 0x0E, 0x10, 0xC0, 0x00, 0xF0, 0xF0, 0x00, 0xC0, 0x02, 0x08, 0xC0, 0x03, 0xC0, 0xF0,
  0x00, 0x60, 0x01, 0x08, 0xE3, 0x1E, 0x01, 0xE0, 0x00, 0x30, 0x00, 0x04, 0xFF, 0xE0, 0x03, 0x80,
  0x00, 0x18, 0x00, 0x04, 0xFF, 0x80, 0x03, 0x00, 0x00, 0x0C, 0x00, 0x00, 0xFC, 0x00, 0x06, 0x00,
  0x00, 0x00, 0x00, 0x02, 0xF0, 0x00, 0x08, 0x00, 0x00, 0x00, 0x00, 0x01, 0xC0, 0x00, 0x00, 0x00,
  0x00, 0x00, 0x00, 0x01, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00,
  0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00
};

// ======================== 4. STATUS & VARIABEL ===========================
volatile float curSuhu    = 28.5f;
volatile float curRH      = 65.0f;
volatile bool  sensorOk   = false;

volatile bool validated          = false;
volatile bool bootHandshakeDone  = false;
volatile unsigned long lastValidMs = 0;
volatile int  lastHttpCode       = 0;
volatile bool usingCloudServer   = true;

// Perintah Sakelar dari Website RansumKu (3 Saluran Aktif)
volatile bool srvKipas   = false;
volatile bool srvMisting = false;
volatile bool srvPakan   = false;

// Status Aktuator Fisik Saat Ini
bool aktKipas     = false;
bool aktSprinkler = false;

// Mesin Status Kereta Pakan Bolak-Balik (Pangkal A -> Ujung B -> Pangkal A)
enum FeederState { FEEDER_IDLE, FEEDER_MAJU, FEEDER_JEDA_B, FEEDER_MUNDUR, FEEDER_JEDA_A };
FeederState feeder = FEEDER_IDLE;
unsigned long feederMulai = 0;
volatile unsigned long srvDurasiPakanMs = 4000;
const unsigned long JEDA_MS             = 2000;
bool limitBMajuAktif   = true;
bool limitAMundurAktif = true;

unsigned long lastSensorRead = 0;
unsigned long lastDisplay    = 0;
unsigned long syncCounter    = 0;

// ======================= 5. FUNGSI KENDALI AKTUATOR ======================
void setRelay(int pin, bool on) {
  bool sinkLow = RELAY_ACTIVE_LOW ? on : !on;
  if (sinkLow) {
    digitalWrite(pin, LOW);
    pinMode(pin, OUTPUT);
    digitalWrite(pin, LOW);
  } else {
    pinMode(pin, INPUT);
  }
}

void motorMaju()   { digitalWrite(PIN_MOTOR_IN1, HIGH); digitalWrite(PIN_MOTOR_IN2, LOW); }
void motorMundur() { digitalWrite(PIN_MOTOR_IN1, LOW);  digitalWrite(PIN_MOTOR_IN2, HIGH); }
void motorStop()   { digitalWrite(PIN_MOTOR_IN1, LOW);  digitalWrite(PIN_MOTOR_IN2, LOW); }

float hitungTHI(float t, float rh) {
  return (0.8f * t) + ((rh / 100.0f) * (t - 14.4f)) + 46.4f;
}

bool isOnline() {
  return (lastValidMs > 0) && (millis() - lastValidMs < VALID_TIMEOUT_MS);
}

// ======================= 6. ANIMASI LOGO.PNG DI OLED =====================
void drawSparkle(int cx, int cy, int r) {
  display.drawLine(cx - r, cy, cx + r, cy, SSD1306_WHITE);
  display.drawLine(cx, cy - r, cx, cy + r, SSD1306_WHITE);
  if (r >= 3) {
    display.drawPixel(cx - 1, cy - 1, SSD1306_WHITE);
    display.drawPixel(cx + 1, cy - 1, SSD1306_WHITE);
    display.drawPixel(cx - 1, cy + 1, SSD1306_WHITE);
    display.drawPixel(cx + 1, cy + 1, SSD1306_WHITE);
  }
}

void playBootAnimation() {
  // BABAK 1: Scanline Laser Reveal dari Bawah ke Atas (Menampilkan LOGO_RANSUMKU_64 di Tengah)
  for (int revealH = 4; revealH <= 64; revealH += 4) {
    display.clearDisplay();
    int yTop = 64 - revealH;
    display.drawBitmap(32, 0, LOGO_RANSUMKU_64, 64, 64, SSD1306_WHITE);
    if (yTop > 0) {
      display.fillRect(0, 0, 128, yTop, SSD1306_BLACK);
      // Garis laser pemindai dengan titik bercahaya di ujungnya
      display.drawLine(24, yTop, 104, yTop, SSD1306_WHITE);
      display.fillCircle(24, yTop, 1, SSD1306_WHITE);
      display.fillCircle(104, yTop, 1, SSD1306_WHITE);
    }
    display.display();
    delay(22);
  }

  // BABAK 2: Efek Kedip Mata Ayam & Kilau Bintang di Telur Emas + Jengger
  for (int step = 0; step < 6; step++) {
    display.clearDisplay();
    display.drawBitmap(32, 0, LOGO_RANSUMKU_64, 64, 64, SSD1306_WHITE);

    // Kedip mata ayam pada step == 2
    if (step == 2) {
      display.fillCircle(32 + 49, 14, 3, SSD1306_WHITE);
      display.drawLine(32 + 47, 14, 32 + 51, 14, SSD1306_BLACK);
    } else {
      // Titik kilau hidup di mata ayam
      display.drawPixel(32 + 49, 13, SSD1306_WHITE);
    }

    // Kilau bintang bergantian di atas telur emas & grafik
    if (step % 2 == 0) {
      drawSparkle(32 + 34, 38, 3);
      drawSparkle(32 + 16, 16, 2);
    } else {
      drawSparkle(32 + 44, 5, 2);
      drawSparkle(32 + 24, 24, 3);
    }

    display.display();
    delay(95);
  }

  // BABAK 3: Transisi Smooth Slide Logo ke Kiri (x=36 -> x=2) + Muncul Teks RANSUMKU
  for (int t = 0; t <= 10; t++) {
    float p = (float)t / 10.0f;
    float ease = 1.0f - (1.0f - p) * (1.0f - p); // Ease-out kuadratik mulus
    int logoX = 36 - (int)(ease * 34.0f);        // Bergeser dari x=36 ke x=2

    display.clearDisplay();
    display.drawBitmap(logoX, 4, LOGO_RANSUMKU_56, 56, 56, SSD1306_WHITE);
    display.drawPixel(logoX + 43, 4 + 11, SSD1306_WHITE); // Kilau mata ayam

    if (t >= 3) {
      int textX = 62 + (int)((1.0f - ease) * 28.0f);
      display.setTextColor(SSD1306_WHITE);
      display.setTextSize(1);
      display.setCursor(textX, 8);
      display.print("RANSUMKU");

      int lineW = (int)(ease * 62.0f);
      display.drawLine(62, 19, 62 + lineW, 19, SSD1306_WHITE);

      display.setCursor(textX, 24);
      display.print("SMART FARM");
      display.setCursor(textX, 35);
      display.print("IoT CLOUD");
    }
    display.display();
    delay(25);
  }

  // BABAK 4: Pengisian Progress Bar Booting (0% -> 100%)
  for (int pct = 0; pct <= 100; pct += 10) {
    display.clearDisplay();
    display.drawBitmap(2, 4, LOGO_RANSUMKU_56, 56, 56, SSD1306_WHITE);
    display.drawPixel(2 + 43, 4 + 11, SSD1306_WHITE);

    if (pct == 40 || pct == 80) {
      drawSparkle(2 + 30, 4 + 33, 2); // Kilau telur emas
    }

    display.setTextColor(SSD1306_WHITE);
    display.setTextSize(1);
    display.setCursor(62, 8);
    display.print("RANSUMKU");
    display.drawLine(62, 19, 124, 19, SSD1306_WHITE);
    display.setCursor(62, 24);
    display.print("SMART FARM");
    display.setCursor(62, 35);
    display.print("SMKN 2 SBY");

    // Kotak Progress Bar (x=62..124, y=49..56)
    display.drawRect(62, 49, 63, 8, SSD1306_WHITE);
    int fillW = (pct * 59) / 100;
    if (fillW > 0) {
      display.fillRect(64, 51, fillW, 4, SSD1306_WHITE);
    }

    display.display();
    delay(45);
  }
  delay(250);
}

// ======================= 7. PARSER JSON RINGAN ===========================
bool bacaBool(const String& json, const char* key) {
  String k = String("\"") + key + "\"";
  int i = json.indexOf(k);
  if (i < 0) return false;
  int colon = json.indexOf(':', i + k.length());
  if (colon < 0) return false;
  int p = colon + 1;
  while (p < (int)json.length() && (json[p] == ' ' || json[p] == '\t' || json[p] == '\r' || json[p] == '\n')) {
    p++;
  }
  return json.startsWith("true", p);
}

int bacaInt(const String& json, const char* key, int defaultVal) {
  String k = String("\"") + key + "\"";
  int i = json.indexOf(k);
  if (i < 0) return defaultVal;
  int colon = json.indexOf(':', i + k.length());
  if (colon < 0) return defaultVal;
  int p = colon + 1;
  while (p < (int)json.length() && (json[p] == ' ' || json[p] == '\t' || json[p] == '\r' || json[p] == '\n' || json[p] == '"')) {
    p++;
  }
  if (p >= (int)json.length() || json[p] < '0' || json[p] > '9') return defaultVal;
  return json.substring(p).toInt();
}

// =================== 8. KOMUNIKASI LATAR BELAKANG (CORE 0) ===============
int postKeEndpoint(const String& baseUrl, const String& body, String& outResp) {
  HTTPClient http;
  String url = baseUrl + "/api/kandang/" + String(FARM_ID) + "/sensor";
  http.setConnectTimeout(4500);
  http.setTimeout(5500);

  int code = -1;
  if (url.startsWith("https://")) {
    WiFiClientSecure secureClient;
    secureClient.setInsecure(); // Mengizinkan koneksi HTTPS ke domain cPanel tanpa sertifikat CA statis
    if (http.begin(secureClient, url)) {
      http.addHeader("Content-Type", "application/json");
      http.addHeader("Accept", "application/json");
      http.addHeader("User-Agent", "RansumKu-ESP32/4.0");
      http.addHeader("Connection", "close");
      code = http.POST(body);
      if (code > 0) {
        outResp = http.getString();
      }
      http.end();
    }
  } else {
    WiFiClient plainClient;
    if (http.begin(plainClient, url)) {
      http.addHeader("Content-Type", "application/json");
      http.addHeader("Accept", "application/json");
      http.addHeader("User-Agent", "RansumKu-ESP32/4.0");
      http.addHeader("Connection", "close");
      code = http.POST(body);
      if (code > 0) {
        outResp = http.getString();
      }
      http.end();
    }
  }
  return code;
}

void kirimDanValidasi() {
  float suhu = curSuhu;
  float rh   = curRH;
  if (suhu < 10.0f) suhu = 10.0f;
  if (suhu > 55.0f) suhu = 55.0f;
  if (rh < 10.0f)   rh   = 10.0f;
  if (rh > 100.0f)  rh   = 100.0f;

  String body = String("{\"suhu\":") + String(suhu, 1) + ",\"kelembaban\":" + String(rh, 1);
  if (!bootHandshakeDone) {
    body += ",\"boot\":true";
  }
  if (strlen(IOT_TOKEN) > 0) {
    body += String(",\"token\":\"") + IOT_TOKEN + "\"";
  }
  body += "}";

  String resp = "";
  String targetUrl = String(SERVER_URL);
  int code = postKeEndpoint(targetUrl, body, resp);

  // Bila domain cloud tidak terjangkau (misal hotspot sedang offline tanpa internet), otomatis coba server lokal laptop
  if (code != 200 && WiFi.gatewayIP()[0] != 0) {
    String localUrl = String("http://") + WiFi.gatewayIP().toString() + ":8000";
    String localResp = "";
    int localCode = postKeEndpoint(localUrl, body, localResp);
    if (localCode == 200) {
      code = localCode;
      resp = localResp;
      targetUrl = localUrl;
      usingCloudServer = false;
    }
  } else if (code == 200) {
    usingCloudServer = true;
  }

  lastHttpCode = code;

  if (code == 200 && resp.indexOf("\"ok\":true") >= 0) {
    bool wasOnline   = isOnline();
    bool prevKipas   = srvKipas;
    bool prevMisting = srvMisting;
    bool prevPakan   = srvPakan;

    srvKipas   = bacaBool(resp, "relay_kipas");
    srvMisting = bacaBool(resp, "relay_misting");
    srvPakan   = bacaBool(resp, "relay_pakan");

    int durDetik = bacaInt(resp, "durasi_detik", 4);
    if (durDetik >= 1 && durDetik <= 30) {
      srvDurasiPakanMs = (unsigned long)durDetik * 1000UL;
    }

    lastValidMs       = millis();
    validated         = true;
    bootHandshakeDone = true;
    syncCounter++;

    bool berubah = (prevKipas != srvKipas || prevMisting != srvMisting || prevPakan != srvPakan);
    if (!wasOnline) {
      Serial.printf("[WEB TERHUBUNG -> %s] Suhu=%.1fC RH=%.1f%% | Kipas=%s | Pompa=%s | Pakan=%s\n",
                    targetUrl.c_str(), suhu, rh,
                    srvKipas ? "ON" : "OFF",
                    srvMisting ? "ON" : "OFF",
                    srvPakan ? "ON" : "OFF");
    } else if (berubah) {
      Serial.printf("[WEB EVENT] Sakelar Berubah -> Kipas=%s | Pompa=%s | Pakan=%s\n",
                    srvKipas ? "ON" : "OFF",
                    srvMisting ? "ON" : "OFF",
                    srvPakan ? "ON" : "OFF");
    } else if (syncCounter % 5 == 0) {
      Serial.printf("[SYNC OK] Suhu=%.1fC RH=%.1f%% | Kipas=%s | Pompa=%s | Pakan=%s\n",
                    suhu, rh,
                    srvKipas ? "ON" : "OFF",
                    srvMisting ? "ON" : "OFF",
                    srvPakan ? "ON" : "OFF");
    }
  } else {
    if (lastValidMs == 0 || millis() - lastValidMs >= VALID_TIMEOUT_MS) {
      Serial.printf("[HTTP] Gagal terhubung ke %s | Kode: %d\n", targetUrl.c_str(), code);
    }
  }
}

void taskKomunikasi(void* param) {
  unsigned long lastPrintWiFi = 0;
  bool pernahPrintIP = false;

  for (;;) {
    if (WiFi.status() != WL_CONNECTED || WiFi.localIP()[0] == 0) {
      validated = false;
      pernahPrintIP = false;
      unsigned long now = millis();
      if (now - lastPrintWiFi >= 3000) {
        lastPrintWiFi = now;
        Serial.printf("[WiFi] Menunggu koneksi ke '%s' (Status: %d)...\n", WIFI_SSID, (int)WiFi.status());
      }
      vTaskDelay(1000 / portTICK_PERIOD_MS);
      continue;
    }

    if (!pernahPrintIP) {
      Serial.printf("\n=======================================================\n");
      Serial.printf("[WiFi] SUKSES TERHUBUNG KE %s!\n", WIFI_SSID);
      Serial.printf("[WiFi] IP ESP32   : %s\n", WiFi.localIP().toString().c_str());
      Serial.printf("[WiFi] Cloud Web  : %s\n", SERVER_URL);
      Serial.printf("=======================================================\n\n");
      pernahPrintIP = true;
    }

    kirimDanValidasi();
    vTaskDelay(INTERVAL_KIRIM_MS / portTICK_PERIOD_MS);
  }
}

// =================== 9. EVALUASI AKTUATOR & KERETA PAKAN =================
void updateAktuator() {
  if (isOnline()) {
    aktKipas     = srvKipas;
    aktSprinkler = srvMisting;
  } else {
    aktKipas     = false;
    aktSprinkler = false;
  }

  setRelay(PIN_RELAY_KIPAS, aktKipas);
  setRelay(PIN_RELAY_SPRINKLER, aktSprinkler);
}

void updateFeeder() {
  unsigned long now = millis();
  bool wanted = isOnline() && srvPakan;

  if (!wanted) {
    if (feeder != FEEDER_IDLE) {
      motorStop();
      feeder = FEEDER_IDLE;
      Serial.println("[PAKAN] Dihentikan (Standby OFF).");
    }
    return;
  }

  switch (feeder) {
    case FEEDER_IDLE:
      feeder = FEEDER_MAJU;
      feederMulai = now;
      limitBMajuAktif = (digitalRead(PIN_LIMIT_B) == HIGH);
      motorMaju();
      Serial.printf("[PAKAN] Mulai Maju (Pangkal A -> Ujung B, maks %.1f dtk)...\n", srvDurasiPakanMs / 1000.0f);
      break;

    case FEEDER_MAJU:
      if ((limitBMajuAktif && (now - feederMulai >= 300) && digitalRead(PIN_LIMIT_B) == LOW) ||
          (now - feederMulai >= srvDurasiPakanMs)) {
        motorStop();
        feeder = FEEDER_JEDA_B;
        feederMulai = now;
        Serial.println("[PAKAN] Tiba di Ujung B! Jeda 2 detik...");
      }
      break;

    case FEEDER_JEDA_B:
      if (now - feederMulai >= JEDA_MS) {
        feeder = FEEDER_MUNDUR;
        feederMulai = now;
        limitAMundurAktif = (digitalRead(PIN_LIMIT_A) == HIGH);
        motorMundur();
        Serial.printf("[PAKAN] Mulai Mundur (Ujung B -> Pangkal A, maks %.1f dtk)...\n", srvDurasiPakanMs / 1000.0f);
      }
      break;

    case FEEDER_MUNDUR:
      if ((limitAMundurAktif && (now - feederMulai >= 300) && digitalRead(PIN_LIMIT_A) == LOW) ||
          (now - feederMulai >= srvDurasiPakanMs)) {
        motorStop();
        feeder = FEEDER_JEDA_A;
        feederMulai = now;
        Serial.println("[PAKAN] Kembali di Pangkal A! Siklus selesai.");
      }
      break;

    case FEEDER_JEDA_A:
      if (now - feederMulai >= JEDA_MS) {
        feeder = FEEDER_MAJU;
        feederMulai = now;
        limitBMajuAktif = (digitalRead(PIN_LIMIT_B) == HIGH);
        motorMaju();
        Serial.println("[PAKAN] Melanjutkan siklus maju...");
      }
      break;
  }
}

void updateLedBiru() {
  unsigned long now = millis();
  if (isOnline()) {
    digitalWrite(PIN_LED_BIRU, HIGH);
  } else if (WiFi.status() == WL_CONNECTED) {
    digitalWrite(PIN_LED_BIRU, (now / 500) % 2);
  } else {
    digitalWrite(PIN_LED_BIRU, (now / 125) % 2);
  }
}

// ======================== 10. TAMPILAN LAYAR OLED ========================
void renderDisplay() {
  float thi = hitungTHI(curSuhu, curRH);
  const char* labelTHI = "NYAMAN";
  if (thi >= 84.0f)      labelTHI = "BAHAYA";
  else if (thi >= 78.0f) labelTHI = "KRITIS";
  else if (thi >= 72.0f) labelTHI = "WASPADA";

  display.clearDisplay();
  display.setTextColor(SSD1306_WHITE);

  // Baris 1: Header
  display.setTextSize(1);
  display.setCursor(6, 0);
  display.print("RANSUMKU - SMART FARM");
  display.drawLine(0, 9, 127, 9, SSD1306_WHITE);

  // Baris 2: Suhu & Kelembaban DHT22
  display.setCursor(0, 13);
  if (sensorOk) {
    display.printf("Suhu: %.1fC", curSuhu);
    display.setCursor(76, 13);
    display.printf("RH:%d%%", (int)curRH);
  } else {
    display.print("DHT22: Cek Pin D15..");
  }

  // Baris 3: Indeks Kenyamanan THI
  display.setCursor(0, 24);
  display.printf("THI : %.1f [%s]", thi, labelTHI);

  // Baris 4: Status Kipas 12V & Pompa Misting 5V
  display.setCursor(0, 35);
  display.printf("KIPAS:%-3s | POMPA:%-3s",
                 aktKipas ? "ON" : "OFF",
                 aktSprinkler ? "ON" : "OFF");

  // Baris 5: Status Sinkronisasi Cloud / Web
  display.setCursor(0, 46);
  if (isOnline()) {
    display.print(usingCloudServer ? "CLOUD: ONLINE (OK)" : "LOKAL: ONLINE (OK)");
  } else if (WiFi.status() == WL_CONNECTED) {
    display.printf("WEB  : MENUNGGU (%d)", lastHttpCode);
  } else {
    display.print("WIFI : MENYAMBUNG...");
  }

  // Baris 6: Status Motor Kereta Pakan
  display.setCursor(0, 56);
  switch (feeder) {
    case FEEDER_MAJU:   display.print("PAKAN: >> MAJU (A->B)"); break;
    case FEEDER_MUNDUR: display.print("PAKAN: << MUNDUR(B->A)"); break;
    case FEEDER_JEDA_A: display.print("PAKAN: JEDA PANGKAL A"); break;
    case FEEDER_JEDA_B: display.print("PAKAN: JEDA UJUNG B"); break;
    default:            display.print("PAKAN: SIAGA (OFF)"); break;
  }

  display.display();
}

// ============================== 11. SETUP ================================
void setup() {
  // 1. Kunci seketika (detik ke-0) agar Relay Kipas, Pompa, dan Motor MATI TOTAL
  setRelay(PIN_RELAY_KIPAS, false);
  setRelay(PIN_RELAY_SPRINKLER, false);

  digitalWrite(PIN_MOTOR_IN1, LOW);
  digitalWrite(PIN_MOTOR_IN2, LOW);
  pinMode(PIN_MOTOR_IN1, OUTPUT);
  pinMode(PIN_MOTOR_IN2, OUTPUT);
  motorStop();

  Serial.begin(115200);
  delay(200);
  Serial.println("\n====================================================");
  Serial.println("  FIRMWARE ESP32 RANSUMKU (CLOUD & ANIMASI LOGO)    ");
  Serial.println("====================================================");

  // 2. Indikator LED Biru & 2 Limit Switch (INPUT_PULLUP -> ditekan = LOW)
  pinMode(PIN_LED_BIRU, OUTPUT);
  digitalWrite(PIN_LED_BIRU, LOW);
  pinMode(PIN_LIMIT_A, INPUT_PULLUP);
  pinMode(PIN_LIMIT_B, INPUT_PULLUP);
  delay(20);
  Serial.printf("[INIT] Semua Alat Standby OFF | LimitA(D33)=%s | LimitB(D32)=%s\n",
                digitalRead(PIN_LIMIT_A) == HIGH ? "SIAP(HIGH)" : "TERTEKAN/NC(LOW)",
                digitalRead(PIN_LIMIT_B) == HIGH ? "SIAP(HIGH)" : "TERTEKAN/NC(LOW)");

  // 3. Sensor Suhu & Kelembaban DHT22 (Pin D15)
  dhtSensor.setup(PIN_DHT, DHTesp::DHT22);

  // 4. Layar OLED 0.96" I2C (SDA=D21, SCL=D22) + Animasi Pembuka logo.png
  Wire.begin(PIN_OLED_SDA, PIN_OLED_SCL);
  if (!display.begin(SSD1306_SWITCHCAPVCC, 0x3C)) {
    display.begin(SSD1306_SWITCHCAPVCC, 0x3D);
  }
  playBootAnimation();

  // Baca DHT22 pertama kali sebelum loop
  TempAndHumidity initData = dhtSensor.getTempAndHumidity();
  if (!isnan(initData.temperature) && !isnan(initData.humidity)) {
    curSuhu  = initData.temperature;
    curRH    = initData.humidity;
    sensorOk = true;
  }

  // 5. Hubungkan ke WiFi Hotspot
  WiFi.mode(WIFI_STA);
  WiFi.setAutoReconnect(true);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
  Serial.printf("[WiFi] Menghubungkan ke Hotspot: %s ...\n", WIFI_SSID);

  // 6. Jalankan Komunikasi HTTP/HTTPS di Core 0
  xTaskCreatePinnedToCore(taskKomunikasi, "komunikasi", 10240, NULL, 1, NULL, 0);
}

// ============================== 12. LOOP =================================
void loop() {
  unsigned long now = millis();

  if (now - lastSensorRead >= 2000) {
    lastSensorRead = now;
    TempAndHumidity data = dhtSensor.getTempAndHumidity();
    if (!isnan(data.temperature) && !isnan(data.humidity)) {
      curSuhu  = data.temperature;
      curRH    = data.humidity;
      sensorOk = true;
    } else {
      sensorOk = false;
      Serial.println("[DHT22] Peringatan: Data belum terbaca, periksa kabel OUT ke Pin D15.");
    }
  }

  updateAktuator();
  updateFeeder();
  updateLedBiru();

  if (now - lastDisplay >= 250) {
    lastDisplay = now;
    renderDisplay();
  }

  delay(10);
}
