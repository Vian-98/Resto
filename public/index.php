<?php
/**
 * Dashboard Utama - Modern, Clean & Comfortable UI
 */
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Pelanggan;
use App\Meja;
use App\Menu;
use App\Pemesanan;

$judulHalaman = 'Dashboard';

$pelangganModel = new Pelanggan();
$mejaModel = new Meja();
$menuModel = new Menu();
$pemesananModel = new Pemesanan();

$totalPelanggan = count($pelangganModel->semua());
$totalMeja = count($mejaModel->semua());
$totalMenu = count($menuModel->semua());
$daftarPemesanan = $pemesananModel->semuaDenganRelasi();
$totalPemesanan = count($daftarPemesanan);

require_once __DIR__ . '/header.php';
?>

<!-- Action Bar -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="page-title">Dashboard</h1>
    </div>
    <div class="d-flex gap-2">
        <a href="pemesanan.php" class="btn-sm-clean btn-sm-primary">
            <i class="bi bi-plus-lg"></i> Pesan Meja
        </a>
        <a href="laporan.php" class="btn-sm-clean">
            <i class="bi bi-file-earmark-bar-graph"></i> Laporan
        </a>
    </div>
</div>

<!-- 4 Metrics -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <a href="pelanggan.php" class="stat-box">
            <div>
                <div class="stat-box-title">Pelanggan</div>
                <div class="stat-box-num"><?= $totalPelanggan ?></div>
            </div>
            <div class="stat-box-icon"><i class="bi bi-people"></i></div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="meja.php" class="stat-box">
            <div>
                <div class="stat-box-title">Meja</div>
                <div class="stat-box-num"><?= $totalMeja ?></div>
            </div>
            <div class="stat-box-icon"><i class="bi bi-grid-3x3"></i></div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="menu.php" class="stat-box">
            <div>
                <div class="stat-box-title">Menu</div>
                <div class="stat-box-num"><?= $totalMenu ?></div>
            </div>
            <div class="stat-box-icon"><i class="bi bi-egg-fried"></i></div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="pemesanan.php" class="stat-box">
            <div>
                <div class="stat-box-title">Pemesanan</div>
                <div class="stat-box-num"><?= $totalPemesanan ?></div>
            </div>
            <div class="stat-box-icon"><i class="bi bi-calendar-check"></i></div>
        </a>
    </div>
</div>

<!-- Recent Reservations -->
<div class="card-clean">
    <div class="card-clean-header">
        <h6 class="card-clean-title">Pemesanan Terbaru</h6>
        <div class="search-compact">
            <i class="bi bi-search"></i>
            <input type="text" id="searchDashboard" placeholder="Cari...">
        </div>
    </div>

    <div class="table-responsive">
        <table class="table-clean" id="tableDashboard">
            <thead>
                <tr>
                    <th style="width: 36px;">#</th>
                    <th>Pelanggan</th>
                    <th>Meja & Tamu</th>
                    <th>Waktu Reservasi</th>
                    <th>Status</th>
                    <th class="text-end">Total</th>
                    <th class="text-center" style="width: 80px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($daftarPemesanan)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">Belum ada pemesanan.</td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $no = 1;
                    foreach (array_slice($daftarPemesanan, 0, 8) as $row): 
                        $statusSlug = strtolower($row['status']);
                    ?>
                        <tr>
                            <td class="text-muted" style="font-size: 12px;"><?= $no++ ?></td>
                            <td>
                                <div class="fw-semibold text-dark"><?= htmlspecialchars($row['nama_pelanggan']) ?></div>
                                <div class="text-secondary" style="font-size: 11.5px;"><?= htmlspecialchars($row['telepon']) ?></div>
                            </td>
                            <td>
                                <span class="fw-semibold text-dark">Meja <?= htmlspecialchars((string)$row['nomor_meja']) ?></span>
                                <span class="text-secondary" style="font-size: 11.5px;"> &bull; <?= htmlspecialchars((string)$row['jumlah_tamu']) ?> orang</span>
                            </td>
                            <td>
                                <div class="text-dark"><?= date('d/m/Y', strtotime($row['waktu_pesan'])) ?></div>
                                <div class="text-secondary" style="font-size: 11.5px;"><?= date('H:i', strtotime($row['waktu_pesan'])) ?> WIB</div>
                            </td>
                            <td>
                                <span class="badge-status <?= $statusSlug ?>">
                                    <?= htmlspecialchars($row['status']) ?>
                                </span>
                            </td>
                            <td class="text-end fw-semibold"><?= format_rupiah((float)$row['total']) ?></td>
                            <td class="text-center">
                                <a href="detail_pesanan.php?pemesanan_id=<?= $row['id'] ?>" class="btn-action" title="Kelola item menu">
                                    <i class="bi bi-card-checklist"></i> Menu
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    setupTableSearch('searchDashboard', 'tableDashboard');
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
