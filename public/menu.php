<?php
/**
 * CRUD Menu (F-03) - Clean & Comfortable UI
 */
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Menu;

$menuModel = new Menu();
$aksi = $_GET['aksi'] ?? 'index';
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Proses Form POST (Tambah / Ubah)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = validasi_input($_POST['nama'] ?? '');
    $harga = isset($_POST['harga']) && is_numeric($_POST['harga']) ? (float)$_POST['harga'] : 0.0;
    $editId = isset($_POST['id']) && is_numeric($_POST['id']) ? (int)$_POST['id'] : null;

    if ($nama === false || $harga <= 0) {
        set_flash('danger', 'Nama menu dan harga valid wajib diisi.');
        redirect('menu.php');
    }

    $menu = new Menu($nama, $harga, $editId);

    if ($menu->simpan()) {
        $pesan = $editId ? 'Menu berhasil diperbarui.' : 'Menu baru berhasil ditambahkan.';
        set_flash('success', $pesan);
    } else {
        set_flash('danger', 'Gagal menyimpan menu.');
    }
    redirect('menu.php');
}

// Proses Hapus GET
if ($aksi === 'hapus' && $id !== null) {
    if ($menuModel->hapus($id)) {
        set_flash('success', 'Menu berhasil dihapus.');
    } else {
        set_flash('danger', 'Gagal menghapus (menu terkait transaksi pesanan).');
    }
    redirect('menu.php');
}

// Data untuk Edit jika aksi=edit
$dataEdit = null;
if ($aksi === 'edit' && $id !== null) {
    $dataEdit = $menuModel->cari($id);
    if (!$dataEdit) {
        set_flash('warning', 'Menu tidak ditemukan.');
        redirect('menu.php');
    }
}

$daftarMenu = $menuModel->semua();
$judulHalaman = 'Menu';

require_once __DIR__ . '/header.php';
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="page-title">Menu</h1>
    </div>
    
    <div class="d-flex gap-2 align-items-center">
        <div class="search-compact">
            <i class="bi bi-search"></i>
            <input type="text" id="searchMenu" placeholder="Cari...">
        </div>
        <button type="button" class="btn-sm-clean btn-sm-primary" data-bs-toggle="modal" data-bs-target="#modalTambahMenu">
            <i class="bi bi-plus-lg"></i> Menu
        </button>
    </div>
</div>

<!-- Table -->
<div class="card-clean">
    <div class="table-responsive">
        <table class="table-clean" id="tableMenu">
            <thead>
                <tr>
                    <th style="width: 36px;">#</th>
                    <th>Nama Menu</th>
                    <th class="text-end" style="width: 140px;">Harga Satuan</th>
                    <th class="text-center" style="width: 120px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($daftarMenu)): ?>
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted">Belum ada menu terdaftar.</td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($daftarMenu as $item): ?>
                        <tr>
                            <td class="text-muted" style="font-size: 12px;"><?= $no++ ?></td>
                            <td class="fw-semibold text-dark"><?= htmlspecialchars($item['nama']) ?></td>
                            <td class="text-end fw-semibold"><?= format_rupiah((float)$item['harga']) ?></td>
                            <td class="text-center">
                                <div class="d-inline-flex gap-1">
                                    <a href="menu.php?aksi=edit&id=<?= $item['id'] ?>" class="btn-action">Edit</a>
                                    <a href="menu.php?aksi=hapus&id=<?= $item['id'] ?>" 
                                       class="btn-action btn-action-danger"
                                       onclick="return confirm('Hapus menu <?= htmlspecialchars($item['nama']) ?>?');">Hapus</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah Menu -->
<div class="modal fade" id="modalTambahMenu" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="menu.php" method="POST">
                <div class="modal-header">
                    <h6 class="modal-title">Menu Baru</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="namaMenuTambah" class="form-label">Nama Menu</label>
                        <input type="text" class="form-control" id="namaMenuTambah" name="nama" placeholder="Nama menu makanan / minuman" required>
                    </div>
                    <div class="mb-3">
                        <label for="hargaTambah" class="form-label">Harga Satuan (Rp)</label>
                        <input type="number" class="form-control" id="hargaTambah" name="harga" min="500" step="500" placeholder="25000" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-sm-clean" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-sm-clean btn-sm-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Menu -->
<?php if ($dataEdit): ?>
<div class="modal fade" id="modalEditMenu" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="menu.php" method="POST">
                <input type="hidden" name="id" value="<?= $dataEdit['id'] ?>">
                <div class="modal-header">
                    <h6 class="modal-title">Edit Menu</h6>
                    <a href="menu.php" class="btn-close" aria-label="Close"></a>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="namaMenuEdit" class="form-label">Nama Menu</label>
                        <input type="text" class="form-control" id="namaMenuEdit" name="nama" 
                               value="<?= htmlspecialchars($dataEdit['nama']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="hargaEdit" class="form-label">Harga Satuan (Rp)</label>
                        <input type="number" class="form-control" id="hargaEdit" name="harga" min="500" step="500" 
                               value="<?= htmlspecialchars((string)$dataEdit['harga']) ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="menu.php" class="btn-sm-clean">Batal</a>
                    <button type="submit" class="btn-sm-clean btn-sm-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new bootstrap.Modal(document.getElementById('modalEditMenu')).show();
});
</script>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    setupTableSearch('searchMenu', 'tableMenu');
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
