# 🌐 PANDUAN SIMULASI WOKWI TERBARU: SMART CLOSED-HOUSE IOT (RANSUMKU)
**Inovasi:** Smart Closed-House & Travelling Hopper Feeder berbasis ESP32  
**Inovator:** Aditya Ramadhani (SMKN 2 Surabaya)  
**Platform:** RansumKu - Top 10 Finalis Lomba Inovasi Peternakan Nasional

---

## 1. Fitur Unggulan Versi Terbaru (Versi Siap Demo Juri)

1. **⚡ Auto-Run Live Demo (Otomatis Berjalan Tanpa Ketik Apapun)**:
   - Begitu tombol hijau **Play ▶** diklik, motor penarik pakan **langsung otomatis meluncur jalan**, relay kipas & misting atap langsung aktif, dan layar OLED langsung menyala!
   - Sangat praktis saat presentasi di depan juri karena Anda tidak perlu repot mengetik perintah di Serial Monitor.
2. **📟 Layar OLED Display 128x64 SSD1306**:
   - Menampilkan telemetri *real-time*: Suhu (°C), Kelembaban (% RH), Indeks THI Ilmiah (Aman/Waspada/Kritis/Bahaya), Status Kipas, Status Sprinkler Atap, dan arah gerak kereta pakan.
3. **🔄 Siklus Kereta Pakan Bolak-Balik Otomatis (*Auto-Shuttle Loop*)**:
   - Motor servo berayun aktif menarik tali pakan maju dari **Pangkal A $\to$ Ujung B** (5 detik).
   - Sampai di B, motor jeda 2 detik, lalu otomatis berputar balik mundur dari **Ujung B $\to$ Pangkal A** (5 detik).
   - Siklus berulang otomatis terus-menerus.
4. **💡 Lampu Indikator Relay Riil**:
   - Terminal `COM` relay terhubung ke 5V sehingga **LED Cyan (Kipas)** dan **LED Biru (Sprinkler Atap)** menyala terang saat relay aktif.
5. **🧮 Formula THI Ilmiah NRC / Zulovich & DeShazer (1990)**:
   $$THI = 0.8 \times T + \frac{RH}{100} \times (T - 14.4) + 46.4$$
   Formula ini **100% identik dan selaras** dengan sistem kalkulasi web RansumKu.

---

## 2. Cara Menjalankan Simulasi di Wokwi (Hanya 1 Menit)

1. Buka browser dan kunjungi: **[https://wokwi.com/projects/new/esp32](https://wokwi.com/projects/new/esp32)**
2. Salin 3 file dari folder `wokwi/` ke project Wokwi Anda:
   * **Tab `diagram.json`:** Buka file `wokwi/diagram.json`, salin seluruh isinya, dan tempel ke tab `diagram.json` di Wokwi. *(Semua komponen, kabel rapi, OLED, servo, relay, dan sensor langsung muncul di layar)*.
   * **Tab `sketch.ino`:** Buka file `wokwi/sketch.ino`, salin seluruh isinya, dan tempel ke tab `sketch.ino` di Wokwi.
   * **Tab `Library Manager`:** Klik tab Library (ikon tumpukan buku di Wokwi) dan pastikan 4 library berikut terpasang (sesuai isi `wokwi/libraries.txt`):
     ```text
     DHT sensor library for ESPx
     Adafruit SSD1306
     Adafruit GFX Library
     ESP32Servo
     ```
3. Klik tombol hijau **▶ (Play / Start Simulation)** di pojok atas!

---

## 3. Peta Komponen dalam Simulasi

```
       [ SENSOR DHT22 ] (GPIO 15)           [ LAYAR OLED SSD1306 ] (I2C: 21, 22)      [ MOTOR SERVO PAKAN ] (GPIO 18)
          Suhu & Kelembaban                    Monitor Telemetri & THI                   Winch Penarik Kereta Rel
                 │                                      │                                      │
                 └───────────────────────┬──────────────┴──────────────────────────────────────┘
                                         │
                               ┌─────────┴─────────┐
                               │   ESP32 DEVKIT    │
                               └─────────┬─────────┘
                 ┌───────────────────────┼───────────────────────┐
                 │                       │                       │
          [RELAY KIPAS]           [RELAY SPRINKLER]        [LIMIT SWITCH]
            (GPIO 12)               (GPIO 14)              (GPIO 32 & 33)
                 │                       │                       │
          LED Cyan Blower         LED Biru Misting Atap    Batas Pangkal A & Ujung B
```

---

## 4. Skenario Pengujian Interaktif yang Bisa Dicoba

### Skenario 1: Demonstrasi Live Demo Otomatis (Default)
- Cukup klik **Play ▶**.
- Perhatikan **Motor Servo Pakan** di kanan atas langsung berputar bolak-balik menarik kereta.
- Perhatikan **Layar OLED** di tengah menampilkan data suhu 33.5°C, THI 86.6 (BAHAYA), Kipas AKTIF, Atap NYEMPROT, dan status PAKAN MAJU/MUNDUR.
- Perhatikan **LED Cyan (Kipas)** dan **LED Biru (Sprinkler Atap)** menyala terang membuktikan relay bekerja!

### Skenario 2: Mengubah Suhu Lingkungan Secara Interaktif
1. Klik sensor **DHT22** di pojok kiri atas.
2. Geser slider suhu ke **28°C** (suhu sejuk):
   - Layar OLED otomatis berubah menjadi `THI: 75.2 (WASPADA)`.
   - Relay Kipas & Sprinkler otomatis mati (**OFF**), dan kedua LED padam untuk menghemat energi!
3. Geser kembali suhu ke **35°C** (cuaca panas ekstrem):
   - Kipas dan Sprinkler Atap langsung menyala kembali (**ON**)!

### Skenario 3: Uji Tombol Limit Switch Mekanik
1. Saat motor sedang bergerak maju, klik tombol hijau **Limit Switch Pangkal A** atau tombol merah **Limit Switch Ujung B**.
2. Motor langsung berhenti seketika (*safety interlock*), membuktikan proteksi tabrakan mekanis bekerja 100%!
