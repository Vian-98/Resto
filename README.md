# Sistem Pemesanan Restoran (RestoApp)

> **Tugas Praktik Demonstrasi Sertifikasi Programmer — BNSP**  
> Dibuat dengan arsitektur terstruktur dan berorientasi objek (OOP), memanfaatkan library pre-existing, serta memenuhi seluruh kriteria unjuk kerja unit kompetensi BNSP.

---

## 1. Ringkasan Proyek

Aplikasi web sederhana untuk staf restoran yang mencakup fungsionalitas:
- **Pengelolaan Data Master**: Pelanggan, Meja, dan Menu (CRUD).
- **Pemesanan Meja**: Reservasi meja dengan validasi bentrok jadwal dan validasi kapasitas tamu.
- **Rincian Item Pesanan**: Menambahkan pesanan menu ke reservasi dengan perhitungan total otomatis.
- **Ubah Status**: Dipesan &rarr; Selesai / Dibatalkan.
- **Laporan & Export PDF**: Rekapitulasi transaksi dan pendapatan per rentang tanggal serta cetak PDF.

---

## 2. Diagram Arsitektur & Pemodelan Sistem (J.620100.009.01)

Sistem dimodelkan secara visual mencakup relasi basis data, arsitektur kelas OOP, dan flowchart logika terstruktur:
> 💡 **Format Draw.io (`.drawio`) Siap Edit:** Seluruh diagram tersedia dalam format asli yang dapat langsung dibuka dan diedit di [app.diagrams.net](https://app.diagrams.net) melalui master berkas [**`semua_diagram_restoran.drawio`**](file:///Users/favian/Proyek%20/sertifikasi%20bnsp/docs/diagrams/semua_diagram_restoran.drawio) (multi-page) atau berkas per diagram. Panduan lengkap tersedia di [`docs/diagrams/DIAGRAM_SOURCE_CODE.md`](file:///Users/favian/Proyek%20/sertifikasi%20bnsp/docs/diagrams/DIAGRAM_SOURCE_CODE.md).

### 2.1 Entity Relationship Diagram (ERD Basis Data)
Ternormalisasi 3NF dengan 5 tabel utama pada MySQL InnoDB:
```
[pelanggan] (1) <─────── (N) [pemesanan] (1) <─────── (N) [detail_pesanan] (N) ───────> (1) [menu]
                                  │
                                  │ (N)
                                  │
                              (1) [meja]
```
> *Berkas diagram visual:* [erd_diagram.png](file:///Users/favian/Proyek%20/sertifikasi%20bnsp/docs/diagrams/erd_diagram.png)

### 2.2 UML Class Diagram (Paradigma OOP)
Penerapan Abstract Class `BaseModel`, Pewarisan (`Pelanggan`, `Meja`, `Menu`), Enkapsulasi `Pemesanan`, dan Singleton `Database`:
```
                  ┌──────────────────────┐
                  │ <<abstract>>         │
                  │ BaseModel            │
                  └──────────┬───────────┘
                             │ extends
         ┌───────────────────┼───────────────────┐
         │                   │                   │
┌────────┴────────┐ ┌────────┴────────┐ ┌────────┴────────┐
│ Pelanggan       │ │ Meja            │ │ Menu            │
└─────────────────┘ └─────────────────┘ └─────────────────┘
```
> *Berkas diagram visual:* [class_diagram.png](file:///Users/favian/Proyek%20/sertifikasi%20bnsp/docs/diagrams/class_diagram.png)

### 2.3 Flowchart Alur Reservasi & Validasi Terstruktur (J.620100.017.02)
Mulai &rarr; Input Reservasi &rarr; Validasi Kapasitas Meja &rarr; Cek Bentrok Jadwal &rarr; Simpan &rarr; Input Pesanan Menu &rarr; Hitung Total Otomatis &rarr; Selesai / Laporan PDF.
> *Berkas diagram visual:* [flowchart_diagram.png](file:///Users/favian/Proyek%20/sertifikasi%20bnsp/docs/diagrams/flowchart_diagram.png)

### 2.4 UML Use Case Diagram (Identifikasi Kebutuhan & Aktor)
Mendefinisikan batasan sistem (*System Boundary*), aktor operasional (**Staf Pelayan / Kasir**: Mendaftarkan Tamu, Melakukan Reservasi, Mencatat Pesanan Menu, Memperbarui Status) dan aktor manajerial (**Manajer Restoran**: Mengatur Meja, Mengatur Menu, Memantau Laporan Penjualan). Dilengkapi relasi wajib `<<include>>` (Memeriksa Ketersediaan Meja, Memvalidasi Kapasitas, Menghitung Total Biaya, Menyaring Periode Tanggal) dan relasi opsional `<<extend>>` (Mengekspor Laporan ke PDF). Seluruh *use case* diawali kata kerja aktif (*action verbs*) sesuai alur operasional restoran nyata.
> *Berkas diagram visual:* [use_case_diagram.png](file:///Users/favian/Proyek%20/sertifikasi%20bnsp/docs/diagrams/use_case_diagram.png)

### 2.5 UML Activity Diagram (Alur Transaksi Lintas Swimlane)
Visualisasi alur aktivitas lintas swimlane (Pengguna, Sistem Logika Bisnis, dan Basis Data MySQL) mulai dari input reservasi, validasi bertingkat, query jadwal, hingga penyimpanan detail item pesanan.
> *Berkas diagram visual:* [activity_diagram.png](file:///Users/favian/Proyek%20/sertifikasi%20bnsp/docs/diagrams/activity_diagram.png)

---

## 3. Struktur Direktori Proyek

```
restoran/
├── berkas_bnsp/                # 3 Berkas deliverable resmi yang dikumpulkan ke Asesor
│   ├── 1_Lampiran_Koding_Sistem_Pemesanan_Restoran.docx
│   ├── 2_Laporan_Pengembangan_Sistem_Pemesanan_Restoran.docx
│   └── 3_Slide_Presentasi_Demonstrasi_BNSP.pptx
├── composer.json               # Konfigurasi dependensi library (Dompdf, PHPUnit)
├── composer.lock               # Lockfile dependensi
├── phpunit.xml                 # Konfigurasi test suite PHPUnit
├── README.md                   # Dokumentasi lengkap & laporan teknis proyek
├── database/
│   └── schema.sql              # DDL basis data MySQL (5 tabel, FK, indeks, & seed data)
├── docs/                       # Dokumen pendukung & diagram arsitektur
│   ├── diagrams/               # Berkas PNG resolusi tinggi (ERD, Class, Flowchart)
│   └── spesifikasi/            # Berkas acuan & rujukan spesifikasi BNSP
├── logs/
│   └── app.log                 # Catatan aktivitas & penanganan error aplikasi
├── public/                     # Tampilan antarmuka web (UI)
│   ├── assets/css/style.css    # Desain antarmuka modern & nyaman
│   ├── header.php              # Header & navigasi Bootstrap 5
│   ├── footer.php              # Footer template
│   ├── index.php               # Dashboard ringkasan & statistik
│   ├── pelanggan.php           # CRUD Data Pelanggan (F-01)
│   ├── meja.php                # CRUD Data Meja Restoran (F-02)
│   ├── menu.php                # CRUD Data Menu Makanan/Minuman (F-03)
│   ├── pemesanan.php           # Buat & kelola status pemesanan meja (F-04, F-06, F-07)
│   ├── detail_pesanan.php      # Tambah item menu ke pemesanan & hitung subtotal (F-05)
│   └── laporan.php             # Laporan filter tanggal & export PDF via Dompdf (F-08)
├── scripts/                    # Skrip automasi Python untuk membangun dokumen
│   ├── build_docs.sh           # Runner regenerasi seluruh berkas sekaligus
│   ├── generate_diagrams.py    # Generator gambar diagram (Pillow)
│   ├── generate_docx.py        # Generator laporan pengembangan .docx
│   ├── generate_lampiran_koding.py # Generator berkas lampiran koding .docx
│   └── generate_pptx.py        # Generator slide presentasi .pptx
├── src/                        # Logika bisnis & kelas program
│   ├── Database.php            # Koneksi PDO (Encapsulation, Singleton, try-catch)
│   ├── helpers.php             # Fungsi prosedural terstruktur (format rupiah, sanitasi input, log)
│   ├── BaseModel.php           # Abstract class (semua, cari, hapus, abstract simpan/validasi)
│   ├── Pelanggan.php           # Model Pelanggan (extends BaseModel, override simpan)
│   ├── Meja.php                # Model Meja (extends BaseModel, override simpan)
│   ├── Menu.php                # Model Menu (extends BaseModel, override simpan)
│   ├── Pemesanan.php           # Model Pemesanan (protected properties, cek bentrok, hitung total)
│   └── LaporanService.php      # Layanan laporan JOIN multi-tabel & integrasi Dompdf
└── tests/
    └── RestoranTest.php        # Unit test PHPUnit (8 skenario: normal, edge, dan error)
```

---

## 4. Persyaratan Sistem & Instalasi

### Kebutuhan:
- PHP 8.1 atau lebih baru (dengan ekstensi `pdo_mysql`, `mbstring`, `gd`)
- MySQL / MariaDB (misalnya via aplikasi **DBngin** pada port 3306)
- Composer

### Langkah Instalasi:

1. **Clone / Buka Direktori Proyek**:
   ```bash
   cd "/Users/favian/Proyek /sertifikasi bnsp"
   ```

2. **Install Dependensi Composer**:
   ```bash
   composer install
   ```

3. **Import Basis Data**:
   Pastikan MySQL di DBngin aktif (Port 3306, User: `root`, Password: kosong). Import file `database/schema.sql`:
   ```bash
   php -r '
   $sql = file_get_contents("database/schema.sql");
   $pdo = new PDO("mysql:host=127.0.0.1;port=3306", "root", "", [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
   $pdo->exec($sql);
   echo "Database db_restoran berhasil diimport!\n";
   '
   ```

4. **Jalankan Web Server**:
   ```bash
   php -S 127.0.0.1:8000 -t public
   ```
   Buka browser di: [http://127.0.0.1:8000](http://127.0.0.1:8000)

---

## 5. Pengujian Unit Program (PHPUnit)

Pengujian mencakup **8 skenario** sesuai spesifikasi PRD (kasus normal, edge case, validasi error, dan akumulasi menu duplikat):

Jalankan perintah:
```bash
vendor/bin/phpunit
```

### Hasil Pengujian:
```text
PHPUnit 11.5.57 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.4.23
Configuration: /Users/favian/Proyek /sertifikasi bnsp/phpunit.xml

........                                                            8 / 8 (100%)

Time: 00:00.081, Memory: 8.00 MB

OK (8 tests, 15 assertions)
```

| ID Uji | Target Fungsi | Skenario Uji | Hasil Diharapkan | Status |
| :--- | :--- | :--- | :--- | :--- |
| **T-01** | `Pemesanan::hitungTotal()` | Kasus normal: 2 item menu (2x25rb + 3x5rb) | Total = Rp 65.000 | **LULUS** |
| **T-02** | `Pemesanan::hitungTotal()` | Edge case: tanpa item pesanan (kosong) | Total = 0.0 | **LULUS** |
| **T-03** | `Pemesanan::cekBentrok()` | Meja dan tanggal-jam sama persis | Terdeteksi bentrok (`true` / ditolak) | **LULUS** |
| **T-04** | `Pemesanan::cekBentrok()` | Meja sama, tanggal-jam berbeda | Tidak bentrok (`false` / diterima) | **LULUS** |
| **T-05** | `Pemesanan::validasiKapasitas()` | Tamu (5 orang) melebihi kapasitas meja (2 orang) | Ditolak (`false`) | **LULUS** |
| **T-06** | `format_rupiah()` | Format nominal angka 15000 | String `"Rp 15.000"` | **LULUS** |
| **T-07** | `validasi_input()` | Input string kosong / hanya spasi | Ditolak (`false`) | **LULUS** |
| **T-08** | `Pemesanan::tambahItem()` | Menu sama dipesan berulang (duplikat) | Kuantitas digabung/merge, tidak terduplikasi | **LULUS** |

---

## 6. Pemetaan ke Unit Kompetensi BNSP

| Kode Unit | Judul Unit Kompetensi | Bukti Implementasi di Proyek |
| :--- | :--- | :--- |
| **J.620100.009.01** | Menggunakan Spesifikasi Program | Kode dibangun persis mengacu pada PRD, ERD, dan spesifikasi tabel relasi di `database/schema.sql`. |
| **J.620100.016.01** | Menulis Kode Sesuai Guidelines & Best Practices | Sesuai standar PSR-12, penamaan camelCase/PascalCase, 1 kelas per file, fungsi modular pendek, sanitasi `htmlspecialchars()`, dan bebas dari syntax error (`php -l`). |
| **J.620100.017.02** | Mengimplementasikan Pemrograman Terstruktur | File `src/helpers.php` memuat fungsi dengan tipe data ketat (`declare(strict_types=1)`), struktur kendali `if/else`, percabangan `switch`, perulangan `foreach`, dan penanganan error `try/catch`. |
| **J.620100.018.02** | Mengimplementasikan Pemrograman Berorientasi Objek (OOP) | Kelas abstrak `BaseModel`, turunan `Pelanggan`, `Meja`, `Menu` (Inheritance & Overriding), enkapsulasi properti `private`/`protected` dengan getter-setter di `Pemesanan`, serta polimorfisme pada pemanggilan `simpan()`. |
| **J.620100.019.02** | Menggunakan Library Pre-existing | Integrasi `dompdf/dompdf` untuk cetak laporan PDF, `phpunit/phpunit` untuk unit test, `Bootstrap 5` untuk UI responsif, dan `Flatpickr` (MIT, offline-ready) untuk date-time picker. Dilengkapi evaluasi lisensi dan kompatibilitas. |
| **J.620100.021.02** | Menerapkan Akses Basis Data | Operasi CRUD lengkap dengan PDO prepared statements, relasi foreign key, indeks performa `idx_meja_waktu`, dan query multi-tabel JOIN di `LaporanService`. |
| **J.620100.023.02** | Membuat Dokumentasi Kode Program | Seluruh berkas dilengkapi DocBlock standar (`@param`, `@return`, `@throws`), komentar pada logika perhitungan/validasi, dan `README.md` terstruktur. |
| **J.620100.025.02** | Melakukan Debugging | Mengaktifkan error reporting, mekanisme logging ke `logs/app.log`, serta terdokumentasi 4 kasus investigasi bug (temuan &rarr; akar &rarr; perbaikan &rarr; verifikasi). |
| **J.620100.033.02** | Melaksanakan Pengujian Unit Program | Pengujian otomatis 8 test case via PHPUnit mencakup skenario normal, skenario ekstrem, edge cases, dan merge duplikat dengan laporan hasil uji (15 assertions). |

---

## 7. Evaluasi Library Pre-Existing (Unit J.620100.019.02)

| Nama Library | Fungsi dalam Aplikasi | Lisensi | Analisis Keuntungan vs Menulis Mandiri | Status & Kompatibilitas |
| :--- | :--- | :--- | :--- | :--- |
| **dompdf/dompdf** (v3.1.x) | Konversi dokumen HTML/CSS menjadi berkas PDF cetak | LGPL-2.1 | Menulis PDF renderer sendiri dari nol membutuhkan waktu berminggu-minggu dan sangat kompleks dalam kalkulasi font dan layout. Dompdf mempermudah styling menggunakan CSS. | **Aktif**, aman digunakan, kompatibel dengan PHP 8.4. |
| **phpunit/phpunit** (v11.5.x) | Framework pengujian unit otomatis (*unit testing*) | BSD-3-Clause | Standar industri pengujian PHP. Menyediakan test runner, assertions lengkap, dan reporting tanpa perlu membangun test harness manual. | **Aktif**, versi stabil resmi, kompatibel dengan PHP 8.4. |
| **Bootstrap 5** (v5.3.x) | Framework antarmuka pengguna (CSS & JS) | MIT | Menghemat waktu pembuatan tata letak responsif, sistem grid, form, tabel, dan komponen UI yang konsisten dan rapi. | **Aktif**, standar industri web frontend modern. |
| **Flatpickr** (v4.6.13) | Date & time picker interaktif pada form pemesanan | MIT | Date picker bawaan browser tidak konsisten antarpltaform. Flatpickr menyediakan UX seragam, keyboard-typable, offline tanpa CDN eksternal. | **Aktif**, aman, kompatibel semua peramban modern. |

---

## 8. Catatan Kasus Debugging (Unit J.620100.025.02)

Berikut adalah 4 kasus bug nyata yang ditemukan, dianalisis, dan diperbaiki selama siklus pengembangan aplikasi:

### Kasus Bug #1: Total Pemesanan Bernilai Null Saat Pesanan Belum Memiliki Menu
- **Temuan**: Saat reservasi baru dibuat tanpa item makanan/minuman, kolom `total` pada database bernilai `NULL`, menyebabkan fungsi `format_rupiah()` memunculkan *warning deprecated* di PHP 8.4 karena menerima nilai null.
- **Akar Masalah**: Fungsi agregat SQL `SUM(subtotal)` mengembalikan `NULL` apabila tidak ada baris yang cocok di tabel `detail_pesanan`.
- **Perbaikan**: Menggunakan fungsi SQL `COALESCE(SUM(subtotal), 0)` pada query perhitungan total di `Pemesanan::updateTotalPemesanan()`, dan memberikan nilai default `0.0` pada properti model.
- **Verifikasi**: Reservasi baru yang belum memiliki item menu otomatis memiliki total `Rp 0` tanpa ada pesan error/warning PHP.

### Kasus Bug #2: Meja Terdeteksi Bentrok dengan Pemesanan yang Berstatus Dibatalkan
- **Temuan**: Pelanggan tidak dapat memesan Meja 2 pada jam tertentu padahal pemesanan sebelumnya di meja tersebut sudah berstatus "Dibatalkan".
- **Akar Masalah**: Query pada method `cekBentrok()` semula hanya memeriksa `WHERE meja_id = :meja_id AND waktu_pesan = :waktu_pesan` tanpa menyaring status reservasi.
- **Perbaikan**: Menambahkan klausa `AND status != 'Dibatalkan'` ke dalam query `cekBentrok()`.
- **Verifikasi**: Meja yang reservasi sebelumnya telah dibatalkan kini dapat dipesan kembali oleh pelanggan lain dengan sukses.

### Kasus Bug #3: Karakter Khusus pada Input Pelanggan Menyebabkan Kerentanan XSS
- **Temuan**: Input nama pelanggan yang mengandung tag `<script>` atau tanda kutip dieksekusi oleh browser saat ditampilkan pada tabel dashboard.
- **Akar Masalah**: Variabel langsung dicetak ke HTML tanpa sanitasi karakter entitas HTML.
- **Perbaikan**: Mengimplementasikan `htmlspecialchars($trimmed, ENT_QUOTES, 'UTF-8')` pada fungsi `validasi_input()` di `helpers.php` dan pada setiap output tabel di view.
- **Verifikasi**: Input `<script>alert('test')</script>` ditampilkan sebagai teks biasa yang aman dan tidak mengeksekusi script apapun.

### Kasus Bug #4: Duplikasi Baris Item Menu yang Sama pada Pesanan
- **Temuan**: Menambahkan menu yang sama lebih dari sekali pada satu pemesanan menghasilkan baris baru yang terpisah di tabel `detail_pesanan`, alih-alih menjumlahkan kuantitasnya.
- **Akar Masalah**: Query `INSERT INTO detail_pesanan` pada method `tambahItem()` selalu membuat baris baru tanpa memverifikasi apakah `menu_id` yang sama sudah ada untuk `pemesanan_id` tersebut.
- **Perbaikan**: Mengimplementasikan logika merge/upsert di `Pemesanan::tambahItem()` — jika `menu_id` sudah ada, kuantitas dan subtotal di-UPDATE; jika belum ada, baru di-INSERT.
- **Verifikasi**: Menambah menu "Nasi Goreng" ke reservasi yang sudah memilikinya otomatis menaikkan kuantitas menjadi 2, tanpa membuat baris duplikat.

---

## 9. Contoh Demonstrasi OOP (Polymorphism & Overriding)

Berikut contoh kode pembuktian konsep **Polymorphism** yang dapat didemonstrasikan di hadapan Asesor:

```php
use App\BaseModel;
use App\Pelanggan;
use App\Meja;
use App\Menu;

// Array polimorfik yang menampung objek berbeda turunan BaseModel
/** @var BaseModel[] $daftarObjek */
$daftarObjek = [
    new Pelanggan("Joko Anwar", "081999888777"),
    new Meja(10, 6),
    new Menu("Kopi Tubruk", 10000.0)
];

// Eksekusi polimorfisme: memanggil method simpan() pada tipe berbeda
foreach ($daftarObjek as $obj) {
    // PHP secara dinamis menjalankan implementasi simpan() milik masing-masing child class
    $berhasil = $obj->simpan();
    echo get_class($obj) . " disimpan: " . ($berhasil ? "SUKSES" : "GAGAL") . "\n";
}
```
