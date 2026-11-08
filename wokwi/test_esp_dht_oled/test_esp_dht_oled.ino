/**
 * =========================================================================
 * SKETCH UJI COBA CEPAT (TEST BENCH): ESP32 + DHT22 + LAYAR OLED + LED
 * Proyek : RansumKu IoT Smart Farm
 * Peneliti: Aditya Ramadhani (SMKN 2 Surabaya)
 * 
 * TUJUAN:
 *   Menguji apakah ESP32, sensor DHT22, layar OLED 0.96", dan LED
 *   berfungsi 100% normal tanpa perlu menyambung ke WiFi/Server dulu.
 * 
 * SAMBUNGAN PIN (Sesuai Diagram & Hardware Anda):
 *   1. DHT22 (Modul PCB Hitam 3-Pin):
 *      - Pin (+) VCC  -> 3V3 ESP32
 *      - Pin OUT Data -> GPIO 15 (D15) ESP32
 *      - Pin (-) GND  -> GND ESP32
 * 
 *   2. LAYAR OLED 0.96" I2C:
 *      - Pin GND      -> GND ESP32
 *      - Pin VCC      -> 3V3 ESP32
 *      - Pin SCL      -> GPIO 22 (D22) ESP32
 *      - Pin SDA      -> GPIO 21 (D21) ESP32
 * 
 *   3. LED INDIKATOR:
 *      - LED Bawaan ESP32 (Internal Blue LED) pada GPIO 2 (D2)
 *      - LED Eksternal (Opsional) pada GPIO 27 (D27)
 * 
 * LIBRARY YANG DIBUTUHKAN (Install di Arduino IDE -> Library Manager):
 *   1. "DHT sensor library for ESPx" by beegee_tokyo
 *   2. "Adafruit SSD1306" by Adafruit
 *   3. "Adafruit GFX Library" by Adafruit
 * =========================================================================
 */

#include <Wire.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>
#include "DHTesp.h"

// --- KONFIGURASI PIN ---
const int PIN_DHT           = 15; // Pin Data DHT22
const int PIN_LED_ONBOARD   = 2;  // LED Biru bawaan board ESP32 (GPIO 2)
const int PIN_LED_EKSTERNAL = 27; // LED Lampu / indikator eksternal (GPIO 27)

// --- KONFIGURASI LAYAR OLED SSD1306 I2C (128x64 piksel) ---
#define SCREEN_WIDTH 128
#define SCREEN_HEIGHT 64
#define OLED_RESET    -1
Adafruit_SSD1306 display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, OLED_RESET);

// --- OBJEK SENSOR DHT22 ---
DHTesp dht;

// --- VARIABEL SENSOR & WAKTU ---
float suhu = 0.0;
float kelembaban = 0.0;
float thi = 0.0;
bool statusSensorOk = false;
unsigned long waktuBacaTerakhir = 0;
const unsigned long INTERVAL_BACA_MS = 1500; // Baca sensor tiap 1.5 detik
uint32_t counterBaca = 0;

// Rumus Temperature-Humidity Index (THI) Peternakan Unggas (Zulovich & DeShazer)
float hitungTHI(float t, float rh) {
  return (0.8f * t) + ((rh / 100.0f) * (t - 14.4f)) + 46.4f;
}

// Menentukan Kategori Kenyamanan Ayam berdasarkan nilai THI
const char* getKategoriTHI(float val) {
  if (val >= 84.0f) return "BAHAYA";
  if (val >= 78.0f) return "KRITIS";
  if (val >= 72.0f) return "WASPADA";
  return "NYAMAN";
}

void setup() {
  // 1. Inisialisasi Serial Monitor untuk debugging lewat Laptop
  Serial.begin(115200);
  delay(500);
  Serial.println("\n==============================================");
  Serial.println("  UJI COBA ESP32 + DHT22 + LAYAR OLED + LED   ");
  Serial.println("         RansumKu Smart Poultry Farm          ");
  Serial.println("==============================================");

  // 2. Inisialisasi Pin LED
  pinMode(PIN_LED_ONBOARD, OUTPUT);
  pinMode(PIN_LED_EKSTERNAL, OUTPUT);
  digitalWrite(PIN_LED_ONBOARD, LOW);
  digitalWrite(PIN_LED_EKSTERNAL, LOW);

  // 3. Inisialisasi I2C (SDA=21, SCL=22) dan Layar OLED
  Wire.begin(21, 22);
  Serial.print("[OLED] Memulai Layar SSD1306 (0x3C)... ");
  if (!display.begin(SSD1306_SWITCHCAPVCC, 0x3C)) {
    Serial.println("GAGAL! Cek kabel SDA (21), SCL (22), VCC, dan GND.");
    // Jika alamat default 0x3C gagal, coba alamat 0x3D
    if (!display.begin(SSD1306_SWITCHCAPVCC, 0x3D)) {
      Serial.println("[OLED] Alamat 0x3D juga gagal. Periksa perkabelan OLED!");
    }
  } else {
    Serial.println("BERHASIL!");
    display.clearDisplay();
    display.setTextColor(SSD1306_WHITE);
    display.setTextSize(1);
    display.setCursor(10, 15);
    display.println("RANSUMKU IoT TEST");
    display.setCursor(15, 30);
    display.println("Memulai Sensor...");
    display.setCursor(20, 48);
    display.println("SMKN 2 SURABAYA");
    display.display();
    delay(1200);
  }

  // 4. Inisialisasi Sensor DHT22 pada GPIO 15
  Serial.print("[DHT22] Menginisialisasi sensor pada Pin D15... ");
  dht.setup(PIN_DHT, DHTesp::DHT22);
  Serial.println("SIAP!");

  // Kedipkan LED 3x cepat tanda startup sukses
  for (int i = 0; i < 3; i++) {
    digitalWrite(PIN_LED_ONBOARD, HIGH);
    digitalWrite(PIN_LED_EKSTERNAL, HIGH);
    delay(100);
    digitalWrite(PIN_LED_ONBOARD, LOW);
    digitalWrite(PIN_LED_EKSTERNAL, LOW);
    delay(100);
  }
}

void loop() {
  unsigned long sekarang = millis();

  // Baca DHT22 secara berkala (tanpa delay blocking)
  if (sekarang - waktuBacaTerakhir >= INTERVAL_BACA_MS) {
    waktuBacaTerakhir = sekarang;
    counterBaca++;

    TempAndHumidity data = dht.getTempAndHumidity();

    if (isnan(data.temperature) || isnan(data.humidity)) {
      statusSensorOk = false;
      Serial.printf("[DHT22 #%u] GAGAL membaca sensor! Cek sambungan kabel Pin D15.\n", counterBaca);
      
      // Kedipkan LED cepat tanda error
      digitalWrite(PIN_LED_ONBOARD, HIGH);
      delay(50);
      digitalWrite(PIN_LED_ONBOARD, LOW);
    } else {
      statusSensorOk = true;
      suhu = data.temperature;
      kelembaban = data.humidity;
      thi = hitungTHI(suhu, kelembaban);

      // Nyalakan LED (heartbeat pulse) saat pembacaan sukses
      digitalWrite(PIN_LED_ONBOARD, HIGH);
      digitalWrite(PIN_LED_EKSTERNAL, HIGH);

      // Cetak data ke Serial Monitor
      Serial.println("----------------------------------------------");
      Serial.printf("[BACA #%u] Suhu: %.1f C | Kelembaban: %.1f %% | THI: %.1f (%s)\n", 
                    counterBaca, suhu, kelembaban, thi, getKategoriTHI(thi));

      // Update Tampilan pada Layar OLED
      updateTampilanOLED();

      // Matikan kembali LED setelah sekejap (memberikan efek kedip denyut jantung)
      delay(80);
      digitalWrite(PIN_LED_ONBOARD, LOW);
      digitalWrite(PIN_LED_EKSTERNAL, LOW);
    }
  }
}

// Fungsi menampilkan data Suhu, Kelembaban, dan THI ke Layar OLED
void updateTampilanOLED() {
  display.clearDisplay();
  display.setTextColor(SSD1306_WHITE);

  // --- HEADER ---
  display.setTextSize(1);
  display.setCursor(4, 0);
  display.print("RANSUMKU IoT MONITOR");
  display.drawLine(0, 9, 127, 9, SSD1306_WHITE);

  if (statusSensorOk) {
    // --- SUHU (HURUF BESAR SIZE 2 BIAR JELAS TERBACA) ---
    display.setTextSize(2);
    display.setCursor(4, 14);
    display.printf("%.1f", suhu);
    display.setTextSize(1);
    display.setCursor(54, 14);
    display.print("o"); // Simbol derajat
    display.setTextSize(2);
    display.setCursor(62, 14);
    display.print("C");

    // --- KELEMBABAN (RH) DI SAMPING KANAN ---
    display.setTextSize(1);
    display.setCursor(84, 14);
    display.print("RH:");
    display.setTextSize(1);
    display.setCursor(84, 24);
    display.printf("%d%%", (int)kelembaban);

    // Garis pemisah tengah
    display.drawLine(0, 33, 127, 33, SSD1306_WHITE);

    // --- BARIS 3: THI DAN STATUS KANDANG ---
    display.setTextSize(1);
    display.setCursor(2, 38);
    display.printf("THI : %.1f [%s]", thi, getKategoriTHI(thi));

    // --- BARIS 4: STATUS LED & DETAK JANTUNG ---
    display.setCursor(2, 52);
    // Tanda bintang bergantian * / + menunjukkan layar aktif bergerak
    char simbolDetak = (counterBaca % 2 == 0) ? '*' : '+';
    display.printf("LED/DHT OK [%c] #%u", simbolDetak, counterBaca);

  } else {
    // Tampilan jika kabel DHT22 belum terhubung dengan benar
    display.setTextSize(1);
    display.setCursor(10, 20);
    display.print("SENSOR DHT22 ERROR!");
    display.setCursor(4, 35);
    display.print("Periksa Kabel D15 /");
    display.setCursor(4, 48);
    display.print("Daya 3.3V & GND.");
  }

  display.display();
}

