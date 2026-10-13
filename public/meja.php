<?php
/**
 * CRUD Meja (F-02) - Clean & Comfortable UI
 */
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Meja;

$mejaModel = new Meja();
$aksi = $_GET['aksi'] ?? 'index';
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Proses Form POST (Tambah / Ubah)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomor = validasi_angka_positif($_POST['nomor'] ?? null);
    $kapasitas = validasi_angka_positif($_POST['kapasitas'] ?? null);
    $editId = isset($_POST['id']) && is_numeric($_POST['id']) ? (int)$_POST['id'] : null;

    if ($nomor === false || $kapasitas === false) {
        set_flash('danger', 'Nomor meja dan kapasitas kursi wajib berupa angka positif.');
        redirect('meja.php');
    }

    $meja = new Meja($nomor, $kapasitas, $editId);

    if ($meja->simpan()) {
        $pesan = $editId ? 'Data meja diperbarui.' : 'Meja baru ditambahkan.';
        set_flash('success', $pesan);
    } else {
        set_flash('danger', 'Gagal menyimpan meja (nomor meja mungkin sudah terdaftar).');
    }
    redirect('meja.php');
}

// Proses Hapus GET
if ($aksi === 'hapus' && $id !== null) {
    if ($mejaModel->hapus($id)) {
        set_flash('success', 'Meja dihapus.');
    } else {
        set_flash('danger', 'Gagal menghapus meja (terkait pemesanan aktif).');
    }
    redirect('meja.php');
}

// Data untuk Edit
$dataEdit = null;
if ($aksi === 'edit' && $id !== null) {
    $dataEdit = $mejaModel->cari($id);
    if (!$dataEdit) {
        set_flash('warning', 'Meja tidak ditemukan.');
        redirect('meja.php');
    }
}

$daftarMeja = $mejaModel->semua();
$judulHalaman = 'Meja';

require_once __DIR__ . '/header.php';
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="page-title">Meja</h1>
    </div>
    
    <div class="d-flex gap-2 align-items-center">
        <div class="search-compact">
            <i class="bi bi-search"></i>
            <input type="text" id="searchMeja" placeholder="Cari...">
        </div>
        <button type="button" class="btn-sm-clean btn-sm-primary" data-bs-toggle="modal" data-bs-target="#modalTambahMeja">
            <i class="bi bi-plus-lg"></i> Meja
        </button>
    </div>
</div>

<!-- Table -->
<div class="card-clean">
    <div class="table-responsive">
        <table class="table-clean" id="tableMeja">
            <thead>
                <tr>
                    <th style="width: 36px;">#</th>
                    <th>Nomor Meja</th>
                    <th>Kapasitas</th>
                    <th class="text-center" style="width: 120px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($daftarMeja)): ?>
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted">Belum ada meja terdaftar.</td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($daftarMeja as $m): ?>
                        <tr>
                            <td class="text-muted" style="font-size: 12px;"><?= $no++ ?></td>
                            <td class="fw-semibold text-dark">Meja <?= htmlspecialchars((string)$m['nomor']) ?></td>
                            <td class="text-secondary"><?= htmlspecialchars((string)$m['kapasitas']) ?> kursi</td>
                            <td class="text-center">
                                <div class="d-inline-flex gap-1">
                                    <a href="meja.php?aksi=edit&id=<?= $m['id'] ?>" class="btn-action">Edit</a>
                                    <a href="meja.php?aksi=hapus&id=<?= $m['id'] ?>" 
                                       class="btn-action btn-action-danger"
                                       onclick="return confirm('Hapus Meja <?= htmlspecialchars((string)$m['nomor']) ?>?');">Hapus</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah Meja -->
<div class="modal fade" id="modalTambahMeja" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="meja.php" method="POST">
                <div class="modal-header">
                    <h6 class="modal-title">Meja Baru</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nomorTambah" class="form-label">Nomor Meja</label>
                        <input type="number" class="form-control" id="nomorTambah" name="nomor" min="1" placeholder="Nomor meja" required>
                    </div>
                    <div class="mb-3">
                        <label for="kapasitasTambah" class="form-label">Kapasitas Kursi</label>
                        <input type="number" class="form-control" id="kapasitasTambah" name="kapasitas" min="1" max="50" placeholder="Kapasitas tamu" required>
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

<!-- Modal Edit Meja -->
<?php if ($dataEdit): ?>
<div class="modal fade" id="modalEditMeja" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="meja.php" method="POST">
                <input type="hidden" name="id" value="<?= $dataEdit['id'] ?>">
                <div class="modal-header">
                    <h6 class="modal-title">Edit Meja</h6>
                    <a href="meja.php" class="btn-close" aria-label="Close"></a>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nomorEdit" class="form-label">Nomor Meja</label>
                        <input type="number" class="form-control" id="nomorEdit" name="nomor" min="1" 
                               value="<?= htmlspecialchars((string)$dataEdit['nomor']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="kapasitasEdit" class="form-label">Kapasitas Kursi</label>
                        <input type="number" class="form-control" id="kapasitasEdit" name="kapasitas" min="1" max="50" 
                               value="<?= htmlspecialchars((string)$dataEdit['kapasitas']) ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="meja.php" class="btn-sm-clean">Batal</a>
                    <button type="submit" class="btn-sm-clean btn-sm-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new bootstrap.Modal(document.getElementById('modalEditMeja')).show();
});
</script>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    setupTableSearch('searchMeja', 'tableMeja');
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
