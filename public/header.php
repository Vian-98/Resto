<?php
/**
 * Header Template - Minimalist & Comfortable UI
 * Unit Kompetensi: J.620100.016.01, J.620100.019.02 (Bootstrap 5)
 */
declare(strict_types=1);

date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/../vendor/autoload.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$halamanAktif = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($judulHalaman) ? htmlspecialchars($judulHalaman) . ' - ' : '' ?>RestoApp</title>
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Flatpickr (Modern Date & Time Picker) -->
    <link href="assets/vendor/flatpickr/flatpickr.min.css" rel="stylesheet">
    <!-- Clean & Comfortable Stylesheet -->
    <link href="assets/css/style.css?v=<?= file_exists(__DIR__ . '/assets/css/style.css') ? filemtime(__DIR__ . '/assets/css/style.css') : time() ?>" rel="stylesheet">
</head>
<body>
    <nav class="app-navbar">
        <div class="app-container d-flex justify-content-between align-items-center">
            <a class="brand-badge" href="index.php">
                <div class="brand-icon"><i class="bi bi-cup-hot-fill"></i></div>
                <span>RestoApp</span>
            </a>
            
            <div class="d-flex align-items-center gap-1">
                <a class="nav-link-custom <?= $halamanAktif === 'index' ? 'active' : '' ?>" href="index.php">Dashboard</a>
                <a class="nav-link-custom <?= $halamanAktif === 'pemesanan' || $halamanAktif === 'detail_pesanan' ? 'active' : '' ?>" href="pemesanan.php">Pemesanan</a>
                <a class="nav-link-custom <?= $halamanAktif === 'pelanggan' ? 'active' : '' ?>" href="pelanggan.php">Pelanggan</a>
                <a class="nav-link-custom <?= $halamanAktif === 'meja' ? 'active' : '' ?>" href="meja.php">Meja</a>
                <a class="nav-link-custom <?= $halamanAktif === 'menu' ? 'active' : '' ?>" href="menu.php">Menu</a>
                <a class="nav-link-custom <?= $halamanAktif === 'laporan' ? 'active' : '' ?>" href="laporan.php">Laporan</a>
            </div>
        </div>
    </nav>

    <main class="main-wrapper">
        <div class="app-container">
            <?php tampilkan_flash(); ?>
