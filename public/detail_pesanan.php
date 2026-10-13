<?php
/**
 * Detail Pesanan / Tambah Item Menu ke Pemesanan (F-05) - Clean & Comfortable UI
 */
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Pemesanan;
use App\Menu;

$pemesananModel = new Pemesanan();
$menuModel = new Menu();

$pemesananId = isset($_GET['pemesanan_id']) ? (int)$_GET['pemesanan_id'] : 0;
if ($pemesananId <= 0) {
    set_flash('danger', 'ID Pemesanan tidak valid.');
    redirect('pemesanan.php');
}

$pemesanan = $pemesananModel->cariDenganRelasi($pemesananId);
if (!$pemesanan) {
    set_flash('warning', 'Pemesanan tidak ditemukan.');
    redirect('pemesanan.php');
}

// Proses Tambah Item Menu (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_item'])) {
    $menuId = validasi_angka_positif($_POST['menu_id'] ?? null);
    $jumlah = validasi_angka_positif($_POST['jumlah'] ?? null, 1, 999);

    if ($menuId === false || $jumlah === false) {
        set_flash('danger', 'Pilihan menu dan jumlah pesanan wajib diisi.');
        redirect("detail_pesanan.php?pemesanan_id={$pemesananId}");
    }

    if ($pemesananModel->simpanItemPesanan($pemesananId, $menuId, $jumlah)) {
        set_flash('success', 'Item menu ditambahkan.');
    } else {
        set_flash('danger', 'Gagal menambahkan item.');
    }
    redirect("detail_pesanan.php?pemesanan_id={$pemesananId}");
}

// Proses Hapus Item Menu (GET)
if (isset($_GET['aksi']) && $_GET['aksi'] === 'hapus_item' && isset($_GET['detail_id'])) {
    $detailId = (int)$_GET['detail_id'];
    if ($pemesananModel->hapusItemDetail($detailId, $pemesananId)) {
        set_flash('success', 'Item menu dihapus.');
    } else {
        set_flash('danger', 'Gagal menghapus item.');
    }
    redirect("detail_pesanan.php?pemesanan_id={$pemesananId}");
}

// Proses Ubah Qty Item Menu (GET)
if (isset($_GET['aksi']) && $_GET['aksi'] === 'ubah_qty' && isset($_GET['detail_id']) && isset($_GET['delta'])) {
    $detailId = (int)$_GET['detail_id'];
    $delta = (int)$_GET['delta'];

    $daftarSekarang = $pemesananModel->ambilItemsDetail($pemesananId);
    $itemDitemukan = null;
    foreach ($daftarSekarang as $it) {
        if ((int)$it['id'] === $detailId) {
            $itemDitemukan = $it;
            break;
        }
    }

    if ($itemDitemukan) {
        $currJumlah = (int)$itemDitemukan['jumlah'];
        $newJumlah = $currJumlah + $delta;
        if ($newJumlah > 0) {
            $pemesananModel->ubahJumlahItem($detailId, $pemesananId, $newJumlah);
            set_flash('success', "Jumlah {$itemDitemukan['nama_menu']} diubah menjadi {$newJumlah}.");
        } else {
            $pemesananModel->hapusItemDetail($detailId, $pemesananId);
            set_flash('info', "{$itemDitemukan['nama_menu']} dihapus dari pesanan.");
        }
    }
    redirect("detail_pesanan.php?pemesanan_id={$pemesananId}");
}

$daftarItem = $pemesananModel->ambilItemsDetail($pemesananId);
$daftarMenu = $menuModel->semua();
$pemesanan = $pemesananModel->cariDenganRelasi($pemesananId);
$statusSlug = strtolower($pemesanan['status']);

$judulHalaman = "Pesanan #{$pemesananId}";
require_once __DIR__ . '/header.php';
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex align-items-center gap-2">
        <a href="pemesanan.php" class="btn-sm-clean" title="Kembali ke Pemesanan">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
        <h1 class="page-title mb-0">Pesanan #<?= $pemesananId ?></h1>
    </div>
    
    <div>
        <span class="badge-status <?= $statusSlug ?>"><?= htmlspecialchars($pemesanan['status']) ?></span>
    </div>
</div>

<!-- Compact Info Bar -->
<div class="card-clean p-3 mb-3">
    <div class="row g-3">
        <div class="col-sm-3">
            <div class="text-secondary" style="font-size: 11.5px; font-weight: 500;">Pelanggan</div>
            <div class="fw-semibold text-dark mt-1"><?= htmlspecialchars($pemesanan['nama_pelanggan']) ?></div>
            <div class="text-secondary" style="font-size: 11.5px;"><?= htmlspecialchars($pemesanan['telepon']) ?></div>
        </div>
        <div class="col-sm-3">
            <div class="text-secondary" style="font-size: 11.5px; font-weight: 500;">Meja</div>
            <div class="fw-semibold text-dark mt-1">Meja <?= htmlspecialchars((string)$pemesanan['nomor_meja']) ?></div>
            <div class="text-secondary" style="font-size: 11.5px;"><?= htmlspecialchars((string)$pemesanan['jumlah_tamu']) ?> orang</div>
        </div>
        <div class="col-sm-3">
            <div class="text-secondary" style="font-size: 11.5px; font-weight: 500;">Waktu</div>
            <div class="fw-semibold text-dark mt-1"><?= date('d/m/Y', strtotime($pemesanan['waktu_pesan'])) ?></div>
            <div class="text-secondary" style="font-size: 11.5px;"><?= date('H:i', strtotime($pemesanan['waktu_pesan'])) ?> WIB</div>
        </div>
        <div class="col-sm-3 text-sm-end">
            <div class="text-secondary" style="font-size: 11.5px; font-weight: 500;">Total Tagihan</div>
            <div class="fs-5 fw-bold text-dark mt-1"><?= format_rupiah((float)$pemesanan['total']) ?></div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Form Tambah Item -->
    <div class="col-md-4">
        <div class="card-clean p-3">
            <h6 class="fw-bold mb-3" style="font-size: 13px;">Tambah Menu</h6>
            <form action="detail_pesanan.php?pemesanan_id=<?= $pemesananId ?>" method="POST">
                <input type="hidden" name="tambah_item" value="1">

                <div class="mb-3">
                    <label for="menu_id" class="form-label">Menu</label>
                    <select class="form-select" id="menu_id" name="menu_id" required>
                        <option value="">Pilih Menu</option>
                        <?php foreach ($daftarMenu as $m): ?>
                            <option value="<?= $m['id'] ?>">
                                <?= htmlspecialchars($m['nama']) ?> &bull; <?= format_rupiah((float)$m['harga']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="jumlah" class="form-label">Jumlah Porsi</label>
                    <input type="number" class="form-control" id="jumlah" name="jumlah" min="1" max="99" value="1" required>
                </div>

                <button type="submit" class="btn-sm-clean btn-sm-primary w-100 justify-content-center py-2">
                    <i class="bi bi-plus-lg"></i> Tambah ke Pesanan
                </button>
            </form>
        </div>
    </div>

    <!-- Table Rincian Item -->
    <div class="col-md-8">
        <div class="card-clean">
            <div class="card-clean-header">
                <h6 class="card-clean-title">Item Pesanan</h6>
                <span class="text-secondary" style="font-size: 12px;"><?= count($daftarItem) ?> item</span>
            </div>

            <div class="table-responsive">
                <table class="table-clean">
                    <thead>
                        <tr>
                            <th style="width: 36px;">#</th>
                            <th>Menu</th>
                            <th class="text-end">Harga</th>
                            <th class="text-center" style="width: 100px;">Qty</th>
                            <th class="text-end">Subtotal</th>
                            <th class="text-center" style="width: 50px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($daftarItem)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">Belum ada item menu yang dipilih.</td>
                            </tr>
                        <?php else: ?>
                            <?php $no = 1; foreach ($daftarItem as $item): ?>
                                <tr>
                                    <td class="text-muted" style="font-size: 12px;"><?= $no++ ?></td>
                                    <td class="fw-semibold text-dark"><?= htmlspecialchars($item['nama_menu']) ?></td>
                                    <td class="text-end text-secondary"><?= format_rupiah((float)$item['harga']) ?></td>
                                    <td class="text-center">
                                        <div class="d-inline-flex align-items-center border rounded bg-white shadow-xs" style="overflow: hidden;">
                                            <a href="detail_pesanan.php?pemesanan_id=<?= $pemesananId ?>&aksi=ubah_qty&detail_id=<?= $item['id'] ?>&delta=-1" 
                                               class="btn-qty-sm text-decoration-none px-2 py-1 text-secondary bg-light border-end" 
                                               title="Kurangi 1">
                                                <i class="bi bi-dash"></i>
                                            </a>
                                            <span class="px-2 fw-semibold text-dark" style="min-width: 26px; font-size: 12px;"><?= (int)$item['jumlah'] ?></span>
                                            <a href="detail_pesanan.php?pemesanan_id=<?= $pemesananId ?>&aksi=ubah_qty&detail_id=<?= $item['id'] ?>&delta=1" 
                                               class="btn-qty-sm text-decoration-none px-2 py-1 text-secondary bg-light border-start" 
                                               title="Tambah 1">
                                                <i class="bi bi-plus"></i>
                                            </a>
                                        </div>
                                    </td>
                                    <td class="text-end fw-semibold"><?= format_rupiah((float)$item['subtotal']) ?></td>
                                    <td class="text-center">
                                        <a href="detail_pesanan.php?pemesanan_id=<?= $pemesananId ?>&aksi=hapus_item&detail_id=<?= $item['id'] ?>" 
                                           class="btn-action btn-action-danger"
                                           title="Hapus"
                                           onclick="return confirm('Hapus <?= htmlspecialchars($item['nama_menu']) ?>?');">
                                            <i class="bi bi-trash3"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr style="background-color: #f8fafc;">
                                <td colspan="4" class="text-end fw-bold py-2 text-dark">Total:</td>
                                <td class="text-end fw-bold py-2 text-dark"><?= format_rupiah((float)$pemesanan['total']) ?></td>
                                <td></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
