# RansumKu 🌾🥚

Aplikasi web berbahasa Indonesia, mobile-first (ramah dibuka dari HP peternak), khusus untuk **peternak ayam petelur skala kecil yang meramu pakan sendiri**.

Proyek ini dibangun untuk kompetisi dengan topik:
* **Feed Technology:** Optimasi formula ransum biaya terendah (*Least-Cost Ration Formulation*) dengan algoritma Linear Programming Simplex 2-fase.
* **Smart Farming:** Buku kandang digital, dashboard grafik performa (*feed consumption*, *egg production*, *feed cost*, efisiensi per kg telur), serta laporan mingguan otomatis.
* **Artificial Intelligence & Anomaly Detection:** Deteksi dini pola anomali kandang berbasis aturan statistik (*rule-based* & z-score) serta asisten tanya-jawab pakan cerdas didukung Google Gemini API (dengan fallback offline tanpa crash).

---

## 🌟 Fitur Utama (3 Modul)

### 1. Modul A: Peracik Ransum (Feed Technology)
* **Input Peternak:** Memasukkan harga riil per kg dan batas stok kg yang tersedia di gudang.
* **Fleksibilitas Jumlah Campuran:** Opsi input langsung total kg campuran **atau** dihitung otomatis dari `jumlah hari × jumlah ayam × konsumsi per ekor per hari`.
* **Acuan Gizi Terbuka (SNI):** Target gizi (Protein Kasar, Kalsium, Fosfor Tersedia, Energi Metabolis, Serat Kasar) dapat diedit dan mencantumkan sumber/tahun acuan (tidak di-hardcode).
* **Solusi Layak & Tidak Layak:** Jika kombinasi bahan/stok tidak memenuhi target gizi, aplikasi memberikan pesan ramah dan saran perbaikan spesifik per nutrisi (bukan error mentah).
* **Hasil Formulasi:**
  * Komposisi resep dalam persen (%) dan kilogram (kg) dengan bilah visual warna-warni.
  * Estimasi total biaya dan biaya per kg pakan.
  * Tabel Cek Gizi dengan indikator visual lulus/belum (✔ / ✘).
  * Simulasi sisa stok gudang dan **daftar belanja bahan** jika stok menipis.
  * Tombol **Hitung Ulang** instan dan tombol AI **"Jelaskan hasil ini"**.

### 2. Modul B: Buku Kandang Digital (Smart Farming)
* **Form Catatan Harian Cepat (1-2 menit):** Tanggal, pakan terpakai (kg), produksi telur (butir & kg), mortalitas ayam, dan biaya operasional lain.
* **Dashboard Visual Interaktif (Chart.js):**
  * Grafik tren konsumsi pakan harian.
  * Grafik produksi telur (butir & egg production % Hen-Day).
  * Grafik tren biaya pakan harian (estimasi).
  * Grafik efisiensi: biaya pakan per kg telur & rasio konversi pakan per butir.
* **Laporan Ringkas Mingguan:** Perbandingan otomatis 7 hari terakhir vs 7 hari sebelumnya.
* **Pencatatan Belanja Nyata:** Mencatat pembelian bahan pakan di toko dan menghitung otomatis **selisih estimasi vs kenyataan**.

### 3. Modul C: Asisten Cerdas (AI & Anomaly Detection)
* **Deteksi Dini Anomali (Rule-Based & Z-Score):**
  * Deteksi pakan naik &ge; 10% selama 3 hari berturut-turut sementara produksi telur turun.
  * Deteksi lonjakan kematian ayam harian melebihi ambang batas variasi normal.
  * Deteksi lonjakan biaya pakan per kg telur tajam (z-score &ge; 2).
  * Kartu peringatan dilengkapi disclaimer penting: *"Ini perkiraan dari pola data, bukan diagnosis. Periksa ayam langsung atau hubungi dokter hewan."*
* **Konsultasi AI (Google Gemini):**
  * Chat interaktif seputar saran bahan pakan lokal pengganti dan tips pemeliharaan pakan.
  * Tombol *"Jelaskan hasil ini"* pada resep ransum untuk menerangkan alasan pemilihan komposisi bahan.
  * **Fallback Aman:** Jika kunci API belum diisi atau koneksi internet offline, aplikasi tetap berjalan dengan jawaban fallback cerdas tanpa pernah crash.

---

## 🛠️ Tech Stack Modern

* **Backend:** Laravel 13 (PHP 8.4)
* **Database:** SQLite (default siap pakai) / MySQL (MariaDB XAMPP)
* **Frontend:** Blade Templating + Tailwind CSS v4 + Vite + Google Material Symbols
* **Grafik:** Chart.js 4
* **Desain UI:** Mobile-first Design System *RansumKu Agritech* (dieksplorasi dari Google Stitch) dengan font **Plus Jakarta Sans**
* **Solver Optimasi:** Linear Programming Simplex Dua-Fase murni PHP (`App\Services\RationOptimizer`)
* **AI:** Google Gemini API (`App\Services\AiAssistant`)

---

## 🚀 Panduan Menjalankan di Komputer Lokal / XAMPP

### Persyaratan
* PHP &ge; 8.2 (direkomendasikan PHP 8.4) dengan ekstensi `pdo_sqlite` atau `pdo_mysql`, `curl`, `mbstring`, `openssl`.
* Composer & Node.js (&ge; 18)

### Langkah Cepat (SQLite Bawaan)
1. Buka terminal PowerShell / Command Prompt di folder proyek:
   ```bash
   cd c:\Users\Aditya\Desktop\lombaternak\ransumku
   ```
2. Jalankan migrasi dan isi database contoh:
   ```bash
   php artisan migrate:fresh --seed
   ```
3. Bangun asset CSS & JS (sudah ter-compile):
   ```bash
   npm run build
   ```
   *(Atau jalankan `npm run dev` jika ingin mode live-reload).*
4. Jalankan server lokal:
   ```bash
   php artisan serve
   ```
5. Buka di browser: [http://localhost:8000](http://localhost:8000)

### Opsi Menggunakan MySQL (XAMPP)
Jika ingin menggunakan database MySQL dari XAMPP:
1. Buka XAMPP Control Panel dan nyalakan **MySQL**.
2. Buat database baru bernama `ransumku` (misal via phpMyAdmin: [http://localhost/phpmyadmin](http://localhost/phpmyadmin)).
3. Edit file `.env` di dalam folder `ransumku`:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=ransumku
   DB_USERNAME=root
   DB_PASSWORD=
   ```
4. Jalankan migrasi dan seeder:
   ```bash
   php artisan migrate:fresh --seed
   ```

---

## 🔑 Akun Demo & Data Uji Siap Pakai

Database seeder telah menyiapkan data lengkap agar aplikasi langsung hidup saat didemokan:

* **Email:** `demo@ransumku.test`
* **Kata Sandi:** `password`
* **Kandang:** Kandang Maju Jaya (200 ayam, fase produksi)
* **Bahan Pakan Bawaan:** Jagung (Rp 6.500/kg), Dedak Padi (Rp 4.500/kg), Konsentrat Layer (Rp 13.000/kg), Kapur/Tepung Kulit Kerang (Rp 2.500/kg).
* **Catatan 14 Hari:** 11 hari catatan stabil normal dan **3 hari terakhir sengaja diset anomali** (konsumsi pakan naik, produksi telur turun) sehingga kartu peringatan dini langsung aktif di Beranda.

### Tombol / Perintah Setel Ulang Data Demo
Jika data sudah banyak diubah saat demonstrasi dan ingin dikembalikan ke kondisi awal demo:
* Klik tombol **"Isi data demo"** di menu **Lainnya** &rarr; Pengaturan Demo, atau
* Jalankan melalui terminal:
  ```bash
  php artisan ransumku:demo
  ```

---

## 🧪 Menjalankan Pengujian Otomatis (Unit & Feature Tests)

Untuk memvalidasi keakuratan Linear Programming Optimizer dan seluruh alur fitur aplikasi:
```bash
php artisan test
```
Hasil pengujian: **10/10 tests passed (45 assertions)** mencakup:
1. Formulasi ransum optimal 40 kg data seeder (Jagung 19,8 kg, Dedak 7,6 kg, Konsentrat 10,5 kg, Kapur 2,0 kg; Biaya Rp 305.000; Nutrisi PK 16,5%, Ca 3,25%, Fosfor 0,76%, Energi 2.700 kkal).
2. Perubahan komposisi saat harga dedak naik ke Rp 7.000/kg.
3. Penanganan kondisi ransum tidak layak tanpa error teknis.
4. Peringatan stok total kurang dari kebutuhan campuran.
5. Uji pembanding ransum formula tebakan (22/10/8/0) yang terbukti defisit gizi.
6. Otentikasi, peracik ransum, buku kandang, dan asisten AI fallback.

---

## 📸 5 Rekomendasi Tangkapan Layar (Screenshot) untuk Proposal Lomba

1. **Beranda Pusat Kendali (Mobile View):**
   * Menampilkan kartu peringatan dini anomali (*"Pakan naik 3 hari berturut-turut sementara telur turun"*), disclaimer AI, 3 metrik biaya ransum/biaya per kg telur, dan grafik mini 7 hari.
2. **Formulir Racik Ransum (`/ransum/baru`):**
   * Menampilkan daftar bahan pakan dengan kolom harga per kg dan batas stok, toggle pilihan jumlah campuran per kilogram atau per hari, dan pemilihan acuan gizi SNI.
3. **Halaman Hasil Ransum & Cek Gizi (`/ransum/{id}`):**
   * Kartu ringkasan total biaya estimasi, tabel resep dalam persen (%) dan kilogram, bilah visual komposisi bahan, kartu Cek Gizi dengan centang hijau (Protein, Kalsium, Fosfor, Energi), serta sisa stok dan daftar belanja bahan.
4. **Buku Kandang & Dashboard Performa (`/catatan`):**
   * Formulir catat harian 1-2 menit, 4 grafik Chart.js (konsumsi pakan, produksi butir telur, biaya pakan, efisiensi biaya per kg telur), laporan ringkas mingguan, dan kartu selisih estimasi vs belanja nyata.
5. **Asisten Cerdas & Chat AI (`/asisten`):**
   * Tab Peringatan Dini anomali statistik kandang dan tab Tanya Jawab AI dengan suggestion chips (*"Bahan pengganti jagung?"*), penjelasan berbasis data program, dan disclaimer non-diagnosis.

---

## ⚖️ Kepatuhan & Disclaimer Penting
1. Nilai gizi bahan pada data awal adalah **angka ilustrasi** untuk keperluan simulasi. Peternak dapat mengedit nilai sesuai label kemasan pakan atau tabel uji laboratorium resmi.
2. Seluruh angka formulasi ransum dan nutrisi dihitung secara deterministik oleh **algoritma Linear Programming program**, bukan oleh model bahasa (AI).
3. Peringatan anomali adalah **indikasi statistik pola data**, bukan diagnosis medis veteriner.
4. Estimasi penghematan biaya adalah **potensi matematis**, bukan jaminan keuntungan mutlak.
