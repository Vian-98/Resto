<?php
/**
 * CRUD Pelanggan (F-01) - Clean & Comfortable UI
 */
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Pelanggan;

$pelangganModel = new Pelanggan();
$aksi = $_GET['aksi'] ?? 'index';
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Proses Form POST (Tambah / Ubah)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = validasi_input($_POST['nama'] ?? '');
    $telepon = validasi_input($_POST['telepon'] ?? '');
    $editId = isset($_POST['id']) && is_numeric($_POST['id']) ? (int)$_POST['id'] : null;

    if ($nama === false || $telepon === false) {
        set_flash('danger', 'Nama dan nomor telepon wajib diisi.');
        redirect('pelanggan.php');
    }

    $pelanggan = new Pelanggan($nama, $telepon, $editId);

    if ($pelanggan->simpan()) {
        $pesan = $editId ? 'Data pelanggan diperbarui.' : 'Pelanggan baru ditambahkan.';
        set_flash('success', $pesan);
    } else {
        set_flash('danger', 'Gagal menyimpan pelanggan.');
    }
    redirect('pelanggan.php');
}

// Proses Hapus GET
if ($aksi === 'hapus' && $id !== null) {
    if ($pelangganModel->hapus($id)) {
        set_flash('success', 'Pelanggan dihapus.');
    } else {
        set_flash('danger', 'Gagal menghapus (terkait transaksi).');
    }
    redirect('pelanggan.php');
}

// Data untuk Edit jika aksi=edit
$dataEdit = null;
if ($aksi === 'edit' && $id !== null) {
    $dataEdit = $pelangganModel->cari($id);
    if (!$dataEdit) {
        set_flash('warning', 'Pelanggan tidak ditemukan.');
        redirect('pelanggan.php');
    }
}

$daftarPelanggan = $pelangganModel->semua();
$judulHalaman = 'Pelanggan';

require_once __DIR__ . '/header.php';
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="page-title">Pelanggan</h1>
    </div>
    
    <div class="d-flex gap-2 align-items-center">
        <div class="search-compact">
            <i class="bi bi-search"></i>
            <input type="text" id="searchPelanggan" placeholder="Cari...">
        </div>
        <button type="button" class="btn-sm-clean btn-sm-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i class="bi bi-plus-lg"></i> Pelanggan
        </button>
    </div>
</div>

<!-- Table -->
<div class="card-clean">
    <div class="table-responsive">
        <table class="table-clean" id="tablePelanggan">
            <thead>
                <tr>
                    <th style="width: 36px;">#</th>
                    <th>Nama</th>
                    <th>Nomor Telepon</th>
                    <th class="text-center" style="width: 120px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($daftarPelanggan)): ?>
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted">Belum ada pelanggan terdaftar.</td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($daftarPelanggan as $p): ?>
                        <tr>
                            <td class="text-muted" style="font-size: 12px;"><?= $no++ ?></td>
                            <td class="fw-semibold text-dark"><?= htmlspecialchars($p['nama']) ?></td>
                            <td class="text-secondary"><?= htmlspecialchars($p['telepon']) ?></td>
                            <td class="text-center">
                                <div class="d-inline-flex gap-1">
                                    <a href="pelanggan.php?aksi=edit&id=<?= $p['id'] ?>" class="btn-action">Edit</a>
                                    <a href="pelanggan.php?aksi=hapus&id=<?= $p['id'] ?>" 
                                       class="btn-action btn-action-danger"
                                       onclick="return confirm('Hapus pelanggan <?= htmlspecialchars($p['nama']) ?>?');">Hapus</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="pelanggan.php" method="POST">
                <div class="modal-header">
                    <h6 class="modal-title">Pelanggan Baru</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="namaTambah" class="form-label">Nama</label>
                        <input type="text" class="form-control" id="namaTambah" name="nama" placeholder="Nama pelanggan" required>
                    </div>
                    <div class="mb-3">
                        <label for="teleponTambah" class="form-label">Nomor Telepon</label>
                        <input type="text" class="form-control" id="teleponTambah" name="telepon" placeholder="Nomor telepon" required>
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

<!-- Modal Edit -->
<?php if ($dataEdit): ?>
<div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="pelanggan.php" method="POST">
                <input type="hidden" name="id" value="<?= $dataEdit['id'] ?>">
                <div class="modal-header">
                    <h6 class="modal-title">Edit Pelanggan</h6>
                    <a href="pelanggan.php" class="btn-close" aria-label="Close"></a>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="namaEdit" class="form-label">Nama</label>
                        <input type="text" class="form-control" id="namaEdit" name="nama" 
                               value="<?= htmlspecialchars($dataEdit['nama']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="teleponEdit" class="form-label">Nomor Telepon</label>
                        <input type="text" class="form-control" id="teleponEdit" name="telepon" 
                               value="<?= htmlspecialchars($dataEdit['telepon']) ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="pelanggan.php" class="btn-sm-clean">Batal</a>
                    <button type="submit" class="btn-sm-clean btn-sm-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new bootstrap.Modal(document.getElementById('modalEdit')).show();
});
</script>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    setupTableSearch('searchPelanggan', 'tablePelanggan');
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
