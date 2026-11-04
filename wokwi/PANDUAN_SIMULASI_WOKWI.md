# Panduan Simulasi Wokwi: Maket Stik Es Krim & IoT RansumKu

Simulasi ini sudah **disamakan 1:1** dengan rancangan final maket stik es krim (termasuk **Sakelar Utama di Titik 1**, **Lampu LED Hangat**, **Proteksi Kelembaban `RH >= 75%`**, dan **tanpa sensor bau/buzzer**).

---

## 1. Perbandingan Alat Fisik di Maket vs Perwakilan di Wokwi

Karena Wokwi adalah simulator elektronik di browser, komponen mekanik/air diwakili oleh modul elektronik pada **nomor pin GPIO ESP32 yang 100% sama**:

| Alat di Maket Stik Es Krim | Wujud di Wokwi (`diagram.json`) | Pin ESP32 | Cara Coba di Wokwi |
| :--- | :--- | :--- | :--- |
| **Sakelar Rocker Merah `ON/OFF` (Titik 1)** | `Slide Switch` (`Sakelar Utama ON/OFF`) | `GPIO 26` | Geser sakelar untuk mematikan/menyalakan seluruh maket & layar OLED. |
| **Sensor Suhu & RH `DHT22`** | `DHT22` Putih | `GPIO 15` | Klik sensor lalu geser *slider* Suhu (`°C`) dan Humidity (`% RH`). |
| **Layar `OLED 0.96"` I2C** | `OLED SSD1306 128x64` | `SDA 21`, `SCL 22` | Otomatis menampilkan Suhu, RH, `THI`, status alat, & arah pakan. |
| **Driver `L298N` + Motor Kuning Katrol** | `Servo Motor` (`Motor Katrol Pakan`) | `GPIO 18` | Lengan motor berputar bolak-balik menarik kereta `A -> B` & `B -> A`. |
| **2× Micro Limit Switch (`A` & `B`)** | 2× `Pushbutton` (Hijau = `A`, Merah = `B`) | `A: 33`, `B: 32` | Klik tombol saat motor jalan untuk menghentikan kereta seketika. |
| **Relay Ch-1 + Kipas Plafon** | `Relay Module` + `LED Cyan` | `GPIO 12` | Menyala otomatis saat `Suhu >= 31°C` atau `THI >= 78`. |
| **Relay Ch-2 + Pompa Sprinkler Atap** | `Relay Module` + `LED Biru` | `GPIO 14` | Menyala saat `THI >= 80`, dan **otomatis terkunci OFF bila `RH >= 75%`**. |
| **Lampu LED Hangat Fotoperiode** | `LED Kuning` (`Lampu LED Hangat`) | `GPIO 27` | Menyala selama sistem aktif (fotoperiode ayam petelur). |
| **Pipa & *Nipple Drinker* Ayam** | *(Tidak ada di Wokwi)* | Tanpa Listrik | Murni sistem gravitasi air (*fail-safe* 24 jam tanpa kabel). |

---

## 2. Cara Membuka & Menjalankan di Wokwi (1 Menit)

1. Buka **[https://wokwi.com/projects/new/esp32](https://wokwi.com/projects/new/esp32)** di browser.
2. Pada tab **`diagram.json`**, hapus isinya lalu *paste* seluruh isi file `wokwi/diagram.json`.
3. Pada tab **`sketch.ino`**, hapus isinya lalu *paste* seluruh isi file `wokwi/sketch.ino`.
4. Pada tab **Library Manager** (ikon tumpukan buku), pastikan 4 library sesuai `wokwi/libraries.txt` sudah ditambahkan:
   - `DHT sensor library for ESPx`
   - `Adafruit SSD1306`
   - `Adafruit GFX Library`
   - `ESP32Servo`
5. Klik tombol hijau **Play ▶**!

---

## 3. Empat Pengujian Interaktif di Wokwi

1. **Tes Sakelar Utama Titik 1:** Klik `Sakelar Utama ON/OFF` di kiri tengah -> layar OLED langsung padam dan semua relay/motor berhenti. Klik lagi -> sistem menyala kembali.
2. **Tes Cuaca Sejuk vs Panas:** Klik `DHT22`, turunkan suhu ke `28°C` -> Kipas & Pompa Sprinkler otomatis **OFF**. Naikkan ke `34°C` (`RH 65%`) -> Kipas & Pompa Sprinkler otomatis **ON**.
3. **Tes Kunci Pengaman Lembap (`RH >= 75%`):** Pada suhu `34°C`, naikkan slider *Humidity* `DHT22` ke `80%` -> LED Biru `Pompa Sprinkler Atap` langsung **MATI** dan layar OLED menulis `Atp:LOCK-RH!` supaya kotoran ayam tidak basah/bau, sementara `Kipas Plafon` tetap **ON** mengeringkan udara!
4. **Tes Limit Switch Rel Kereta Pakan:** Saat status pakan `MAJU (A->B)`, tekan tombol merah `Limit Switch Ujung B` -> motor langsung berhenti aman, jeda `2 detik`, lalu berbalik `MUNDUR (B->A)`.
