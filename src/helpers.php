<?php
/**
 * Helpers / Prosedural Helper Functions
 * Unit Kompetensi: J.620100.017.02 (Mengimplementasikan Pemrograman Terstruktur)
 *
 * Menggunakan struktur kontrol: percabangan (if/else, switch),
 * perulangan (foreach), dan tipe data terstruktur.
 */

declare(strict_types=1);

// Set zona waktu default ke Waktu Indonesia Barat (WIB)
date_default_timezone_set('Asia/Jakarta');

/**
 * Format angka nominal ke format mata uang Rupiah
 *
 * @param float|int $angka Nominal angka
 * @return string Hasil format (contoh: "Rp 25.000")
 */
function format_rupiah(float|int $angka): string
{
    if ($angka < 0) {
        return 'Rp 0';
    }
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

/**
 * Validasi dan sanitasi input teks
 *
 * @param mixed $data Nilai input
 * @return string|false Mengembalikan string bersih atau false jika kosong
 */
function validasi_input(mixed $data): string|false
{
    if ($data === null) {
        return false;
    }

    $trimmed = trim((string)$data);
    if ($trimmed === '') {
        return false;
    }

    // Sanitasi karakter spesial untuk mencegah XSS
    return htmlspecialchars($trimmed, ENT_QUOTES, 'UTF-8');
}

/**
 * Validasi angka integer positif (misal: kapasitas, jumlah tamu)
 *
 * @param mixed $nilai
 * @param int $min
 * @param int $max
 * @return int|false
 */
function validasi_angka_positif(mixed $nilai, int $min = 1, int $max = 1000): int|false
{
    if (!is_numeric($nilai)) {
        return false;
    }

    $intVal = (int)$nilai;
    if ($intVal < $min || $intVal > $max) {
        return false;
    }

    return $intVal;
}

/**
 * Catat log aktivitas atau error ke file logs/app.log
 *
 * @param string $pesan Pesan log
 * @param string $level Tingkat log (INFO, WARNING, ERROR)
 * @return void
 */
function catat_log(string $pesan, string $level = 'INFO'): void
{
    $logDir = dirname(__DIR__) . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }

    $logFile = $logDir . '/app.log';
    $waktu = date('Y-m-d H:i:s');

    // Percabangan switch untuk format label level
    switch (strtoupper($level)) {
        case 'ERROR':
            $label = '[ERROR]';
            break;
        case 'WARNING':
            $label = '[WARN] ';
            break;
        case 'INFO':
        default:
            $label = '[INFO] ';
            break;
    }

    $barisLog = "{$waktu} {$label} {$pesan}" . PHP_EOL;
    file_put_contents($logFile, $barisLog, FILE_APPEND);
}

/**
 * Redirect ke halaman lain dan hentikan eksekusi
 *
 * @param string $url
 * @return void
 */
function redirect(string $url): void
{
    header("Location: {$url}");
    exit;
}

/**
 * Set flash session message
 *
 * @param string $tipe success|danger|warning|info
 * @param string $pesan
 * @return void
 */
function set_flash(string $tipe, string $pesan): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash'] = [
        'tipe' => $tipe,
        'pesan' => $pesan,
    ];
}

/**
 * Tampilkan flash message jika ada
 *
 * @return void
 */
function tampilkan_flash(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (isset($_SESSION['flash'])) {
        $tipe = htmlspecialchars($_SESSION['flash']['tipe'], ENT_QUOTES, 'UTF-8');
        $pesan = htmlspecialchars($_SESSION['flash']['pesan'], ENT_QUOTES, 'UTF-8');
        echo "<div class=\"alert alert-{$tipe} alert-dismissible fade show\" role=\"alert\">
                {$pesan}
                <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\" aria-label=\"Close\"></button>
              </div>";
        unset($_SESSION['flash']);
    }
}
