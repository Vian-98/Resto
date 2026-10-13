<?php
/**
 * Laporan Pemesanan & Cetak PDF (F-08) - Clean & Comfortable UI
 */
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\LaporanService;

$laporanService = new LaporanService();

$mulai = $_GET['mulai'] ?? date('Y-m-01');
$selesai = $_GET['selesai'] ?? date('Y-m-d');

// Cetak PDF jika diminta
if (isset($_GET['cetak']) && $_GET['cetak'] === 'pdf') {
    $laporanService->cetakPDF($mulai, $selesai, true);
    exit;
}

$daftarPemesanan = $laporanService->getLaporanRentang($mulai, $selesai);
$ringkasan = $laporanService->getRingkasan($mulai, $selesai);

$judulHalaman = 'Laporan';
require_once __DIR__ . '/header.php';
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="page-title">Laporan</h1>
    </div>
    <a href="laporan.php?mulai=<?= urlencode($mulai) ?>&selesai=<?= urlencode($selesai) ?>&cetak=pdf" 
       target="_blank" 
       class="btn-sm-clean btn-sm-primary">
        <i class="bi bi-file-earmark-pdf"></i> Cetak PDF
    </a>
</div>

<!-- Filter Bar -->
<div class="card-clean p-3 mb-3">
    <form method="GET" action="laporan.php" class="row g-2 align-items-end">
        <div class="col-sm-4">
            <label for="mulai" class="form-label">Dari Tanggal</label>
            <input type="date" class="form-control" id="mulai" name="mulai" value="<?= htmlspecialchars($mulai) ?>" required>
        </div>
        <div class="col-sm-4">
            <label for="selesai" class="form-label">Sampai Tanggal</label>
            <input type="date" class="form-control" id="selesai" name="selesai" value="<?= htmlspecialchars($selesai) ?>" required>
        </div>
        <div class="col-sm-4 d-flex gap-2">
            <button type="submit" class="btn-sm-clean btn-sm-primary flex-grow-1 justify-content-center py-2">
                Tampilkan
            </button>
            <a href="laporan.php" class="btn-sm-clean py-2" title="Reset">Reset</a>
        </div>
    </form>
</div>

<!-- 2 Compact KPI Boxes -->
<div class="row g-3 mb-3">
    <div class="col-sm-6">
        <div class="card-clean p-3">
            <div class="text-secondary" style="font-size: 12px; font-weight: 500;">Total Transaksi</div>
            <div class="fs-4 fw-bold text-dark mt-1"><?= $ringkasan['total_transaksi'] ?> <span class="fs-6 fw-normal text-secondary">pesanan</span></div>
        </div>
    </div>
    <div class="col-sm-6">
        <div class="card-clean p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-secondary" style="font-size: 12px; font-weight: 500;">Total Pendapatan</div>
                <span class="text-muted" style="font-size: 11px;">(Non-batal)</span>
            </div>
            <div class="fs-4 fw-bold text-dark mt-1"><?= format_rupiah($ringkasan['total_pendapatan']) ?></div>
        </div>
    </div>
</div>

<!-- Table Laporan -->
<div class="card-clean">
    <div class="card-clean-header">
        <h6 class="card-clean-title">Data Transaksi (<?= date('d/m/Y', strtotime($mulai)) ?> &ndash; <?= date('d/m/Y', strtotime($selesai)) ?>)</h6>
        <div class="search-compact">
            <i class="bi bi-search"></i>
            <input type="text" id="searchLaporan" placeholder="Cari...">
        </div>
    </div>

    <div class="table-responsive">
        <table class="table-clean" id="tableLaporan">
            <thead>
                <tr>
                    <th style="width: 36px;">#</th>
                    <th>Pelanggan</th>
                    <th>Meja & Tamu</th>
                    <th>Waktu Reservasi</th>
                    <th>Status</th>
                    <th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($daftarPemesanan)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">Tidak ada transaksi pada periode ini.</td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $no = 1; 
                    foreach ($daftarPemesanan as $row): 
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
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    setupTableSearch('searchLaporan', 'tableLaporan');
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
