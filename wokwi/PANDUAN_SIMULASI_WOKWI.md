# Panduan Simulasi Wokwi Lengkap: Maket Stik Es Krim & IoT RansumKu

Simulasi memakai **pin yang sama** dengan firmware fisik `ransumku_esp32/ransumku_esp32.ino`.

## 1. Komponen di Wokwi dan Padanannya di Maket

| Alat di Maket | Wujud di Wokwi | Pin ESP32 | Cara coba |
| :--- | :--- | :--- | :--- |
| Sakelar rocker merah (Titik 1) | `Slide Switch` | GPIO 26 | Geser untuk mematikan/menyalakan semua alat dan OLED |
| Sensor DHT22 | `DHT22` | GPIO 15 | Klik sensor, geser slider suhu dan kelembaban |
| OLED 0.96" | `SSD1306` | SDA 21, SCL 22 | Tampil suhu, RH, THI, status alat dan pakan |
| L298N + motor TT kuning | `Servo` (gerak kereta) + LED hijau IN1 (maju) + LED oranye IN2 (mundur) | IN1 18, IN2 19, ENA 5 | Lihat servo bergerak dan LED arah bergantian |
| Limit switch A (pangkal) | `Pushbutton` hijau | GPIO 33 | Tekan saat kereta mundur |
| Limit switch B (ujung) | `Pushbutton` merah | GPIO 32 | Tekan saat kereta maju |
| Relay Ch-1 + kipas plafon | `Relay` + LED cyan | GPIO 12 | ON bila Suhu >= 31 C atau THI >= 78 |
| Relay Ch-2 + pompa sprinkler | `Relay` + LED biru | GPIO 14 | ON bila THI >= 80, terkunci OFF bila RH >= 75% |
| LED hangat fotoperiode | LED kuning + resistor 220 ohm | GPIO 27 | Menyala selama sistem aktif |
| Nipple drinker | (tidak disimulasikan) | tanpa listrik | sistem gravitasi |

Yang **tidak ada** di Wokwi: baterai 18650, LM2596, L298N asli, dan koneksi WiFi ke web RansumKu. Bagian itu hanya bisa diuji pada rangkaian fisik.

## 2. Cara Menjalankan
1. Buka https://wokwi.com/projects/new/esp32
2. Tab `diagram.json`: ganti isinya dengan `wokwi/diagram.json`.
3. Tab `sketch.ino`: ganti isinya dengan `wokwi/sketch.ino`.
4. Library Manager: tambahkan 4 library dari `wokwi/libraries.txt`.
5. Klik Play.

Kabel di `diagram.json` berupa garis lurus tanpa belokan, jadi tampilannya bisa saling silang. Geser komponen dengan mouse bila ingin dirapikan. Sambungan pin-nya tetap benar.

## 3. Empat Pengujian
1. **Sakelar utama:** geser sakelar, OLED padam dan semua LED mati. Geser lagi, sistem menyala.
2. **Suhu:** set DHT22 ke 28 C, kipas dan sprinkler OFF. Set 34 C dengan RH 65%, keduanya ON.
3. **Kunci kelembapan:** pada 34 C, naikkan RH ke 80%. LED sprinkler mati dan OLED menulis `Atp:LOCK-RH!`, sedangkan kipas tetap ON.
4. **Limit switch:** saat `MAJU`, tekan tombol merah (B), kereta berhenti, jeda 2 detik, lalu mundur. Saat `MUNDUR`, tekan tombol hijau (A). Tanpa ditekan, kereta berhenti otomatis setelah 8 detik sebagai pengaman.
