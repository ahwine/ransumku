# 🌐 PANDUAN SIMULASI WOKWI: SMART CLOSED-HOUSE IOT (RANSUMKU)
**Inovasi:** Smart Closed-House & Travelling Hopper Feeder berbasis ESP32  
**Pengembang:** Aditya Ramadhani (SMKN 2 Surabaya)

---

## 1. Cara Menjalankan Simulasi di Wokwi (Hanya 1 Menit)

1. Buka browser dan kunjungi: **[https://wokwi.com/projects/new/esp32](https://wokwi.com/projects/new/esp32)**
2. Di layar editor Wokwi, Anda akan melihat tab **`sketch.ino`** dan **`diagram.json`**:
   * **Buka tab `diagram.json`:** Hapus semua isinya, lalu **Copy & Paste** isi file `wokwi/diagram.json`. *(Skematik kabel, ESP32, DHT22, Relay, LED, Buzzer, dan Switch akan langsung tergambar otomatis di layar!)*
   * **Buka tab `sketch.ino`:** Hapus semua isinya, lalu **Copy & Paste** isi file `wokwi/sketch.ino`.
   * **Buka tab `Library Manager`** (atau buat file `libraries.txt`): Tambahkan pustaka:
     ```text
     DHT sensor library for ESPx
     ```
3. Klik tombol hijau **▶ (Play / Start Simulation)** di pojok atas!

---

## 2. Peta Komponen dalam Simulasi

```
           [ SENSOR DHT22 ] (GPIO 15)
                  │
        ┌─────────┴─────────┐
        │   ESP32 DEVKIT    │
        └─────────┬─────────┘
   ┌──────────────┼──────────────┬──────────────┬──────────────┐
   │              │              │              │              │
[RELAY 1]      [RELAY 2]      [L298N FEEDER] [LAMPU]       [BUZZER]
(GPIO 12)      (GPIO 14)      (GPIO 18 & 19) (GPIO 27)     (GPIO 25)
   │              │              │              │              │
Kipas Blower   Sprinkler      Kereta Pakan   Penerangan    Audio Relaksasi
Sirkulasi      Atap Seng      Lorong Baterai Fotoperiode   Anti-Stres
```

* **Sensor DHT22 (Kiri):** Mengukur suhu & kelembaban miniatur kandang.
* **Relay 1 + LED Cyan (Kanan Atas):** Kipas Sirkulasi Udara.
* **Relay 2 + LED Biru (Kanan):** Pompa Sprinkler Pendingin Atap.
* **LED Hijau & Oranye (Tengah Kanan):** Simulasi Motor Kereta Pakan (Hijau = Maju, Oranye = Mundur).
* **Pushbutton Merah (Kiri Bawah):** *Limit Switch* (Sakelar pembatas saat kereta sampai di ujung lorong).
* **LED Kuning (Bawah):** Lampu Fotoperiode.
* **Buzzer (Kiri):** Audio Terapi Anti-Stres (*Sound Therapy*).

---

## 3. Skenario Pengujian Interaktif yang Bisa Dicoba

### Skenario 1: Uji Otomatisasi Suhu & THI (Heat Stress)
1. Saat simulasi berjalan, klik komponen **DHT22**.
2. Geser slider suhu ke **35°C** (cuaca panas terik):
   * Dalam 2 detik, **Relay Kipas (LED Cyan)** dan **Relay Sprinkler Atap (LED Biru)** akan otomatis menyala (**ON**)!
   * Serial Monitor mencetak: `[IKLIM] Suhu: 35.0 °C | THI: 87.4 (BAHAYA EKSTREM) -> KIPAS & SPRINKLER ON!`.
3. Geser slider suhu kembali ke **27°C** (suhu sejuk):
   * Kedua relay otomatis mati (**OFF**), menghemat listrik & air!

---

### Skenario 2: Uji Kereta Pakan Berjalan (*Travelling Hopper*)
1. Buka kotak input di **Serial Monitor** (bagian bawah Wokwi).
2. Ketik **`maju`** lalu tekan Enter:
   * **LED Hijau menyala:** Kereta pakan mulai meluncur membagikan ransum ke lorong!
3. Sekarang klik tombol merah **`Limit Switch Ujung`** di layar simulasi (ibaratnya kereta sudah mentok di ujung lorong kandang):
   * Kereta pakan berhenti 1 detik.
   * **LED Oranye menyala:** Kereta otomatis berputar balik (**MUNDUR**) menuju stasiun awal (*docking station*)!
   * Setelah 3 detik, kereta berhenti dan kembali ke status **STANDBY**.

---

### Skenario 3: Perintah Uji Manual via Serial Monitor
Ketik perintah berikut di Serial Monitor Wokwi kapan saja:
* `maju` : Jalankan kereta pakan maju
* `mundur` : Jalankan kereta pakan mundur
* `stop` : Hentikan kereta pakan seketika
* `kipas_on` / `kipas_off` : Manual override sakelar kipas
* `sprinkler_on` / `sprinkler_off` : Manual override sprinkler atap
* `sound_on` / `sound_off` : Bunyikan audio terapi penenang ayam
* `lampu_on` / `lampu_off` : Nyalakan lampu fotoperiode
* `status` : Cetak status seluruh sistem kandang
* `bantuan` : Menampilkan seluruh daftar perintah

