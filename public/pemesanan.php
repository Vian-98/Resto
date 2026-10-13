<?php
/**
 * Pengelolaan Pemesanan (F-04, F-06, F-07) - Modern & Comfortable UI
 */
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Pelanggan;
use App\Meja;
use App\Pemesanan;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pemesananModel = new Pemesanan();
$pelangganModel = new Pelanggan();
$mejaModel = new Meja();

$aksi = $_GET['aksi'] ?? 'index';
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Endpoint AJAX: Cek Ketersediaan Meja & Kapasitas Real-time (Sesuai Activity Diagram)
if ($aksi === 'cek_ketersediaan') {
    header('Content-Type: application/json; charset=utf-8');
    $mejaId = validasi_angka_positif($_GET['meja_id'] ?? null);
    $jumlahTamu = validasi_angka_positif($_GET['jumlah_tamu'] ?? null);
    $waktuPesanRaw = trim((string)($_GET['waktu_pesan'] ?? ''));

    $waktuPesan = false;
    if (!empty($waktuPesanRaw)) {
        $ts = strtotime($waktuPesanRaw);
        if ($ts !== false) {
            $waktuPesan = date('Y-m-d H:i:00', $ts);
        }
    }

    $response = [
        'kapasitas_ok' => true,
        'kapasitas_pesan' => 'Kapasitas mencukupi.',
        'kapasitas_meja' => 0,
        'nomor_meja' => '',
        'bentrok' => false,
        'bentrok_pesan' => 'Jadwal meja tersedia (tidak ada bentrok).'
    ];

    if ($mejaId !== false) {
        $mejaData = $mejaModel->cari($mejaId);
        if ($mejaData) {
            $response['kapasitas_meja'] = (int)$mejaData['kapasitas'];
            $response['nomor_meja'] = (string)$mejaData['nomor'];
            if ($jumlahTamu !== false && $jumlahTamu > $response['kapasitas_meja']) {
                $response['kapasitas_ok'] = false;
                $response['kapasitas_pesan'] = "Kapasitas Meja Tidak Cukup: Meja {$mejaData['nomor']} maksimal {$response['kapasitas_meja']} tamu (diinput {$jumlahTamu} tamu).";
            } else {
                $response['kapasitas_pesan'] = "Kapasitas cukup (Maksimal {$response['kapasitas_meja']} tamu).";
            }
        }
    }

    if ($mejaId !== false && $waktuPesan !== false) {
        if ($pemesananModel->cekBentrok($mejaId, $waktuPesan)) {
            $response['bentrok'] = true;
            $response['bentrok_pesan'] = "Meja Bentrok di Jam Tersebut: Meja {$response['nomor_meja']} sudah memiliki reservasi aktif.";
        }
    }

    echo json_encode($response);
    exit;
}

// Proses Simpan Pemesanan Baru (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_pemesanan'])) {
    $pelangganId = validasi_angka_positif($_POST['pelanggan_id'] ?? null);
    $mejaId = validasi_angka_positif($_POST['meja_id'] ?? null);
    $jumlahTamu = validasi_angka_positif($_POST['jumlah_tamu'] ?? null);

    $tanggalPesan = trim((string)($_POST['tanggal_pesan'] ?? ''));
    $jamPesan = trim((string)($_POST['jam_pesan'] ?? ''));
    $waktuPesanRaw = trim((string)($_POST['waktu_pesan'] ?? ''));

    if (!empty($tanggalPesan) && !empty($jamPesan)) {
        $ts = strtotime("{$tanggalPesan} {$jamPesan}");
        $waktuPesan = ($ts !== false) ? date('Y-m-d H:i:00', $ts) : false;
    } elseif (!empty($waktuPesanRaw)) {
        $ts = strtotime($waktuPesanRaw);
        $waktuPesan = ($ts !== false) ? date('Y-m-d H:i:00', $ts) : false;
    } else {
        $waktuPesan = false;
    }

    // Simpan data draft input agar tidak hilang saat terjadi kesalahan validasi
    $draftData = [
        'pelanggan_id' => ($pelangganId !== false) ? $pelangganId : ($_POST['pelanggan_id'] ?? ''),
        'meja_id' => ($mejaId !== false) ? $mejaId : ($_POST['meja_id'] ?? ''),
        'tanggal_pesan' => $tanggalPesan,
        'jam_pesan' => $jamPesan,
        'jumlah_tamu' => ($jumlahTamu !== false) ? $jumlahTamu : ($_POST['jumlah_tamu'] ?? 2),
    ];

    if ($pelangganId === false || $mejaId === false || $waktuPesan === false || $jumlahTamu === false) {
        $_SESSION['draft_pemesanan'] = $draftData;
        $_SESSION['draft_error'] = 'Semua kolom pemesanan wajib diisi dengan benar.';
        set_flash('danger', 'Semua kolom pemesanan wajib diisi dengan benar.');
        redirect('pemesanan.php');
    }

    // Tahap 1: Validasi Kapasitas Meja (Activity Diagram: Cek Tamu <= Kapasitas Meja)
    if (!$pemesananModel->validasiKapasitas($mejaId, $jumlahTamu)) {
        $mejaData = $mejaModel->cari($mejaId);
        $kap = $mejaData['kapasitas'] ?? '?';
        $noMeja = $mejaData['nomor'] ?? '';
        $pesanErr = "Peringatan Error (Tahap 1): Kapasitas Meja Tidak Cukup. Meja {$noMeja} hanya memuat maksimal {$kap} tamu (Anda memasukkan {$jumlahTamu} tamu).";
        $_SESSION['draft_pemesanan'] = $draftData;
        $_SESSION['draft_error'] = $pesanErr;
        set_flash('danger', $pesanErr);
        redirect('pemesanan.php');
    }

    // Tahap 2: Validasi Bentrok Jadwal (Activity Diagram: Kueri Jadwal Meja Aktif)
    if ($pemesananModel->cekBentrok($mejaId, $waktuPesan)) {
        $mejaData = $mejaModel->cari($mejaId);
        $noMeja = $mejaData['nomor'] ?? '';
        $pesanErr = "Peringatan Error (Tahap 2): Meja Bentrok di Jam Tersebut. Meja {$noMeja} sudah memiliki reservasi aktif pada waktu tersebut.";
        $_SESSION['draft_pemesanan'] = $draftData;
        $_SESSION['draft_error'] = $pesanErr;
        set_flash('danger', $pesanErr);
        redirect('pemesanan.php');
    }

    $pemesanan = new Pemesanan($pelangganId, $mejaId, $waktuPesan, $jumlahTamu, 'Dipesan');

    if ($pemesanan->simpan()) {
        unset($_SESSION['draft_pemesanan'], $_SESSION['draft_error']);
        set_flash('success', 'Pemesanan meja berhasil dibuat.');
    } else {
        $_SESSION['draft_pemesanan'] = $draftData;
        $_SESSION['draft_error'] = 'Gagal menyimpan pemesanan ke basis data.';
        set_flash('danger', 'Gagal membuat pemesanan.');
    }
    redirect('pemesanan.php');
}

// Proses Ubah Status (F-07)
if ($aksi === 'ubah_status' && $id !== null && isset($_GET['status'])) {
    $statusBaru = $_GET['status'];
    if ($pemesananModel->ubahStatus($id, $statusBaru)) {
        set_flash('success', "Status reservasi diubah menjadi {$statusBaru}.");
    } else {
        set_flash('danger', 'Gagal mengubah status.');
    }
    redirect('pemesanan.php');
}

// Proses Hapus Pemesanan
if ($aksi === 'hapus' && $id !== null) {
    if ($pemesananModel->hapus($id)) {
        set_flash('success', 'Pemesanan dihapus.');
    } else {
        set_flash('danger', 'Gagal menghapus pemesanan.');
    }
    redirect('pemesanan.php');
}

$daftarPemesanan = $pemesananModel->semuaDenganRelasi();
$daftarPelanggan = $pelangganModel->semua();
$daftarMeja = $mejaModel->semua();
$judulHalaman = 'Pemesanan';

// Ambil draft jika ada validasi yang gagal (Activity Diagram: Kembali ke alur koreksi tanpa kehilangan input)
$draftPemesanan = $_SESSION['draft_pemesanan'] ?? null;
$draftError = $_SESSION['draft_error'] ?? null;
unset($_SESSION['draft_pemesanan'], $_SESSION['draft_error']);

$draftPelangganId = $draftPemesanan['pelanggan_id'] ?? '';
$draftMejaId = $draftPemesanan['meja_id'] ?? '';
$draftTanggal = !empty($draftPemesanan['tanggal_pesan']) ? $draftPemesanan['tanggal_pesan'] : date('Y-m-d');
$draftJam = !empty($draftPemesanan['jam_pesan']) ? $draftPemesanan['jam_pesan'] : date('H:i');
$draftTamu = isset($draftPemesanan['jumlah_tamu']) ? (int)$draftPemesanan['jumlah_tamu'] : 2;

require_once __DIR__ . '/header.php';
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="page-title">Pemesanan</h1>
    </div>
    
    <div class="d-flex gap-2 align-items-center">
        <div class="search-compact">
            <i class="bi bi-search"></i>
            <input type="text" id="searchPemesanan" placeholder="Cari...">
        </div>
        <button type="button" class="btn-sm-clean btn-sm-primary" data-bs-toggle="modal" data-bs-target="#modalPemesanan">
            <i class="bi bi-plus-lg"></i> Pesan Meja
        </button>
    </div>
</div>

<!-- Table -->
<div class="card-clean">
    <div class="table-responsive">
        <table class="table-clean" id="tablePemesanan">
            <thead>
                <tr>
                    <th style="width: 36px;">#</th>
                    <th>Pelanggan</th>
                    <th>Meja & Tamu</th>
                    <th>Waktu Reservasi</th>
                    <th>Status</th>
                    <th class="text-end">Total</th>
                    <th class="text-center" style="width: 150px;">Aksi</th>
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
                            <td class="text-center">
                                <div class="d-inline-flex gap-1 align-items-center">
                                    <a href="detail_pesanan.php?pemesanan_id=<?= $row['id'] ?>" class="btn-action" title="Kelola item menu">
                                        <i class="bi bi-card-checklist"></i> Menu
                                    </a>
                                    
                                    <div class="dropdown">
                                        <button class="btn-action dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            Status
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border py-1" style="font-size: 12.5px;">
                                            <li><a class="dropdown-item py-1" href="pemesanan.php?aksi=ubah_status&id=<?= $row['id'] ?>&status=Dipesan">Dipesan</a></li>
                                            <li><a class="dropdown-item py-1 text-success" href="pemesanan.php?aksi=ubah_status&id=<?= $row['id'] ?>&status=Selesai">Selesai</a></li>
                                            <li><a class="dropdown-item py-1 text-danger" href="pemesanan.php?aksi=ubah_status&id=<?= $row['id'] ?>&status=Dibatalkan">Dibatalkan</a></li>
                                        </ul>
                                    </div>

                                    <a href="pemesanan.php?aksi=hapus&id=<?= $row['id'] ?>" 
                                       class="btn-action btn-action-danger" 
                                       title="Hapus"
                                       onclick="return confirm('Hapus pemesanan <?= htmlspecialchars($row['nama_pelanggan']) ?>?');">
                                        <i class="bi bi-trash3"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Pemesanan -->
<div class="modal fade" id="modalPemesanan" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="pemesanan.php" method="POST" id="formPemesanan">
                <input type="hidden" name="simpan_pemesanan" value="1">

                <div class="modal-header">
                    <div>
                        <h6 class="modal-title mb-0">Pesan Meja Baru</h6>
                        <small class="text-muted" style="font-size: 11.5px;">Pemeriksaan ketersediaan meja & jadwal otomatis</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if (!empty($draftError)): ?>
                        <!-- Notifikasi Koreksi Data Draft -->
                        <div class="alert alert-danger py-2 px-3 mb-3 d-flex align-items-center gap-2 border-0" role="alert" style="font-size: 12px; border-radius: 8px; background: #fef2f2; color: #991b1b; border: 1px solid #fecaca !important;">
                            <i class="bi bi-exclamation-octagon-fill fs-5 flex-shrink-0"></i>
                            <div class="flex-grow-1">
                                <span class="fw-bold"><?= htmlspecialchars($draftError) ?></span>
                                <div class="small mt-0.5" style="color: #b91c1c;">
                                    Data reservasi Anda tetap tersimpan di formulir ini untuk disesuaikan.
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Status Ketersediaan Meja (Natural & Smooth) -->
                    <div id="barStatusReservasi" class="alert d-flex align-items-center gap-2 py-2 px-3 mb-3 border-0" 
                         style="font-size: 12px; border-radius: 8px; background: #f8fafc; color: #475569; border: 1px solid #e2e8f0 !important; transition: all 0.25s ease;">
                        <i id="iconStatusReservasi" class="bi bi-info-circle text-primary fs-6 flex-shrink-0"></i>
                        <div id="textStatusReservasi" class="flex-grow-1">Pilih meja, waktu, dan jumlah tamu untuk memeriksa ketersediaan.</div>
                    </div>

                    <div class="mb-3">
                        <label for="pelanggan_id" class="form-label">Pelanggan</label>
                        <select class="form-select" id="pelanggan_id" name="pelanggan_id" required>
                            <option value="">Pilih Pelanggan</option>
                            <?php foreach ($daftarPelanggan as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= ((string)$p['id'] === (string)$draftPelangganId) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['nama']) ?> (<?= htmlspecialchars($p['telepon']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="meja_id" class="form-label">Meja Restoran</label>
                        <select class="form-select" id="meja_id" name="meja_id" required>
                            <option value="" data-kapasitas="0" data-nomor="">Pilih Meja</option>
                            <?php foreach ($daftarMeja as $m): ?>
                                <option value="<?= $m['id'] ?>" 
                                        data-kapasitas="<?= (int)$m['kapasitas'] ?>" 
                                        data-nomor="<?= htmlspecialchars((string)$m['nomor']) ?>"
                                        <?= ((string)$m['id'] === (string)$draftMejaId) ? 'selected' : '' ?>>
                                    Meja <?= htmlspecialchars((string)$m['nomor']) ?> (Kapasitas: <?= (int)$m['kapasitas'] ?> tamu)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="hintMeja" class="form-text text-muted" style="font-size: 11.5px;">
                            Pilih meja untuk memeriksa daya tampung dan jadwal.
                        </div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-7">
                            <label for="tanggal_pesan" class="form-label">Tanggal Reservasi</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-calendar3"></i></span>
                                <input type="text" class="form-control bg-white border-start-0" id="tanggal_pesan" name="tanggal_pesan" 
                                       placeholder="YYYY-MM-DD" value="<?= htmlspecialchars($draftTanggal) ?>" required>
                            </div>
                        </div>
                        <div class="col-5">
                            <label for="jam_pesan" class="form-label">Jam (WIB)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-clock"></i></span>
                                <input type="text" class="form-control bg-white border-start-0" id="jam_pesan" name="jam_pesan" 
                                       placeholder="JJ:MM" value="<?= htmlspecialchars($draftJam) ?>" required>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Ambil Waktu Real-time Saat Ini -->
                    <div class="mb-2">
                        <button type="button" id="btnWaktuSekarang" class="btn-sm-clean w-100 justify-content-center py-1.5" style="background: #f1f5f9; border: 1px dashed #cbd5e1; color: #1e293b; font-weight: 600; font-size: 11.5px;">
                            <i class="bi bi-clock-history text-primary"></i> Pakai Waktu Saat Ini (Real-time)
                        </button>
                    </div>

                    <!-- Feedback Ketersediaan Jadwal -->
                    <div class="mb-3">
                        <div id="feedbackJadwal" class="form-text text-muted" style="font-size: 11.5px;">
                            <i class="bi bi-calendar-event me-1"></i> Tentukan waktu untuk verifikasi jadwal meja.
                        </div>
                    </div>

                    <div class="mb-2">
                        <label for="jumlah_tamu" class="form-label">Jumlah Tamu</label>
                        <div class="d-flex align-items-center gap-3">
                            <div class="guest-counter" id="wrapperGuestCounter">
                                <button type="button" id="btnDecGuest" aria-label="Kurangi Tamu"><i class="bi bi-dash"></i></button>
                                <input type="number" id="jumlah_tamu" name="jumlah_tamu" min="1" max="50" value="<?= $draftTamu ?>" required>
                                <button type="button" id="btnIncGuest" aria-label="Tambah Tamu"><i class="bi bi-plus"></i></button>
                            </div>
                            <div id="hintKapasitasTamu" class="text-secondary" style="font-size: 11.5px;">
                                Masukkan jumlah tamu yang akan hadir.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-sm-clean" data-bs-dismiss="modal">Tutup / Batal</button>
                    <button type="submit" id="btnSimpanReservasi" class="btn-sm-clean btn-sm-primary">
                        <i class="bi bi-check-lg"></i> Simpan Reservasi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    setupTableSearch('searchPemesanan', 'tablePemesanan');

    let fpTanggal = null;
    let fpJam = null;

    // Inisialisasi Flatpickr untuk Tanggal & Jam Reservasi (Mendukung Input Langsung dari Keyboard)
    if (typeof flatpickr !== 'undefined') {
        fpTanggal = flatpickr("#tanggal_pesan", {
            dateFormat: "Y-m-d",
            defaultDate: "<?= date('Y-m-d') ?>",
            minDate: "today",
            allowInput: true,
            locale: (typeof flatpickr.l10ns !== 'undefined' && flatpickr.l10ns.id) ? flatpickr.l10ns.id : "default",
            disableMobile: true,
            onChange: function() {
                periksaAlurReservasi();
            }
        });

        fpJam = flatpickr("#jam_pesan", {
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i",
            time_24hr: true,
            defaultDate: "<?= date('H:i') ?>",
            allowInput: true,
            disableMobile: true,
            onChange: function() {
                periksaAlurReservasi();
            }
        });

        // Sinkronisasi jika user mengetik manual dari keyboard
        const elTanggal = document.getElementById('tanggal_pesan');
        if (elTanggal) {
            elTanggal.addEventListener('change', function() {
                if (this.value && fpTanggal) {
                    fpTanggal.setDate(this.value, false);
                }
                periksaAlurReservasi();
            });
        }
        const elJam = document.getElementById('jam_pesan');
        if (elJam) {
            elJam.addEventListener('change', function() {
                if (this.value && fpJam) {
                    fpJam.setDate(this.value, false);
                }
                periksaAlurReservasi();
            });
        }
    }

    // Tombol Pakai Waktu Realtime Saat Ini
    const btnWaktuSekarang = document.getElementById('btnWaktuSekarang');
    if (btnWaktuSekarang) {
        btnWaktuSekarang.addEventListener('click', function() {
            const now = new Date();
            const yyyy = now.getFullYear();
            const mm = String(now.getMonth() + 1).padStart(2, '0');
            const dd = String(now.getDate()).padStart(2, '0');
            const hh = String(now.getHours()).padStart(2, '0');
            const ii = String(now.getMinutes()).padStart(2, '0');

            const tglStr = `${yyyy}-${mm}-${dd}`;
            const jamStr = `${hh}:${ii}`;

            if (fpTanggal) {
                fpTanggal.setDate(tglStr, true);
                fpTanggal.close();
            } else {
                document.getElementById('tanggal_pesan').value = tglStr;
            }

            if (fpJam) {
                fpJam.setDate(jamStr, true);
                fpJam.close();
            } else {
                document.getElementById('jam_pesan').value = jamStr;
            }
            periksaAlurReservasi();
        });
    }

    // Stepper & Direct Typing Tamu
    const guestInput = document.getElementById('jumlah_tamu');
    const btnDec = document.getElementById('btnDecGuest');
    const btnInc = document.getElementById('btnIncGuest');
    const mejaSelect = document.getElementById('meja_id');
    const btnSimpan = document.getElementById('btnSimpanReservasi');

    if (guestInput) {
        guestInput.addEventListener('focus', function() {
            this.select();
        });
        guestInput.addEventListener('input', periksaAlurReservasi);
        guestInput.addEventListener('change', function() {
            let val = parseInt(this.value, 10);
            if (isNaN(val) || val < 1) {
                this.value = 1;
            } else if (val > 50) {
                this.value = 50;
            }
            periksaAlurReservasi();
        });
    }

    if (btnDec && guestInput) {
        btnDec.addEventListener('click', function() {
            let val = parseInt(guestInput.value, 10) || 1;
            if (val > 1) {
                guestInput.value = val - 1;
                periksaAlurReservasi();
            }
        });
    }
    if (btnInc && guestInput) {
        btnInc.addEventListener('click', function() {
            let val = parseInt(guestInput.value, 10) || 1;
            if (val < 50) {
                guestInput.value = val + 1;
                periksaAlurReservasi();
            }
        });
    }

    if (mejaSelect) {
        mejaSelect.addEventListener('change', periksaAlurReservasi);
    }

    // =========================================================================
    // VALIDASI BERTAHAP REAL-TIME (SESUAI LOGIKA SISTEM: KAPASITAS -> JADWAL)
    // =========================================================================
    let timerCekBentrok = null;

    function periksaAlurReservasi() {
        const barStatus = document.getElementById('barStatusReservasi');
        const iconStatus = document.getElementById('iconStatusReservasi');
        const textStatus = document.getElementById('textStatusReservasi');
        const hintMeja = document.getElementById('hintMeja');
        const hintKapasitasTamu = document.getElementById('hintKapasitasTamu');
        const feedbackJadwal = document.getElementById('feedbackJadwal');
        const wrapperGuestCounter = document.getElementById('wrapperGuestCounter');
        const tglInput = document.getElementById('tanggal_pesan');
        const jamInput = document.getElementById('jam_pesan');

        if (!mejaSelect || !guestInput || !barStatus) return;

        const selectedOpt = mejaSelect.options[mejaSelect.selectedIndex];
        const mejaId = mejaSelect.value;
        const kapasitasMeja = selectedOpt ? parseInt(selectedOpt.getAttribute('data-kapasitas') || '0', 10) : 0;
        const nomorMeja = selectedOpt ? selectedOpt.getAttribute('data-nomor') : '';
        const jmlTamu = parseInt(guestInput.value, 10) || 0;

        // Reset default jika belum memilih meja
        if (!mejaId || kapasitasMeja === 0) {
            barStatus.style.background = '#f8fafc';
            barStatus.style.borderColor = '#e2e8f0';
            barStatus.style.color = '#475569';
            if (iconStatus) iconStatus.className = 'bi bi-info-circle text-primary fs-6 flex-shrink-0';
            if (textStatus) textStatus.textContent = 'Pilih meja, waktu, dan jumlah tamu untuk memeriksa ketersediaan.';

            if (wrapperGuestCounter) wrapperGuestCounter.className = 'guest-counter';
            if (hintKapasitasTamu) {
                hintKapasitasTamu.className = 'text-secondary';
                hintKapasitasTamu.textContent = 'Masukkan jumlah tamu yang akan hadir.';
            }
            if (feedbackJadwal) {
                feedbackJadwal.className = 'form-text text-muted';
                feedbackJadwal.innerHTML = '<i class="bi bi-calendar-event me-1"></i> Tentukan waktu untuk verifikasi jadwal meja.';
            }
            if (btnSimpan) {
                btnSimpan.disabled = false;
                btnSimpan.title = '';
            }
            return;
        }

        if (hintMeja) {
            hintMeja.innerHTML = `Meja ${nomorMeja} memiliki daya tampung maksimal <strong>${kapasitasMeja} tamu</strong>.`;
        }

        // ---------------------------------------------------------------------
        // TAHAP 1: VALIDASI KAPASITAS MEJA
        // Jika tamu melebihi kapasitas meja -> Tampilkan koreksi dan tunda cek jadwal
        // ---------------------------------------------------------------------
        if (jmlTamu > kapasitasMeja) {
            barStatus.style.background = '#fef2f2';
            barStatus.style.borderColor = '#fecaca';
            barStatus.style.color = '#991b1b';
            if (iconStatus) iconStatus.className = 'bi bi-exclamation-triangle-fill text-danger fs-6 flex-shrink-0';
            if (textStatus) {
                textStatus.innerHTML = `Jumlah tamu (<strong>${jmlTamu} orang</strong>) melebihi kapasitas Meja ${nomorMeja} (maks. <strong>${kapasitasMeja} orang</strong>). Mohon sesuaikan tamu atau pilih meja lain.`;
            }

            if (wrapperGuestCounter) wrapperGuestCounter.className = 'guest-counter is-invalid';
            if (hintKapasitasTamu) {
                hintKapasitasTamu.className = 'text-danger fw-semibold';
                hintKapasitasTamu.innerHTML = `<i class="bi bi-x-circle-fill me-1"></i> Maksimal ${kapasitasMeja} orang (kelebihan ${jmlTamu - kapasitasMeja} orang)`;
            }

            if (feedbackJadwal) {
                feedbackJadwal.className = 'form-text text-muted';
                feedbackJadwal.innerHTML = '<i class="bi bi-pause-circle me-1"></i> Pemeriksaan jadwal akan aktif setelah kapasitas tamu sesuai.';
            }

            if (btnSimpan) {
                btnSimpan.disabled = true;
                btnSimpan.title = `Kapasitas Meja ${nomorMeja} hanya ${kapasitasMeja} orang`;
            }
            return; // Berhenti di validasi kapasitas
        }

        // Kapasitas Aman
        if (wrapperGuestCounter) wrapperGuestCounter.className = 'guest-counter is-valid';
        if (hintKapasitasTamu) {
            hintKapasitasTamu.className = 'text-success fw-medium';
            hintKapasitasTamu.innerHTML = `<i class="bi bi-check-circle-fill me-1"></i> Kapasitas sesuai (${jmlTamu} dari maks. ${kapasitasMeja} kursi)`;
        }

        // ---------------------------------------------------------------------
        // TAHAP 2: VALIDASI BENTROK JADWAL
        // ---------------------------------------------------------------------
        const tglVal = tglInput ? tglInput.value.trim() : '';
        const jamVal = jamInput ? jamInput.value.trim() : '';

        if (!tglVal || !jamVal) {
            barStatus.style.background = '#f0f9ff';
            barStatus.style.borderColor = '#bae6fd';
            barStatus.style.color = '#0369a1';
            if (iconStatus) iconStatus.className = 'bi bi-info-circle-fill text-info fs-6 flex-shrink-0';
            if (textStatus) textStatus.textContent = 'Kapasitas meja sesuai. Silakan tentukan tanggal dan jam reservasi.';

            if (feedbackJadwal) {
                feedbackJadwal.className = 'form-text text-muted';
                feedbackJadwal.innerHTML = '<i class="bi bi-clock me-1"></i> Masukkan tanggal dan jam reservasi.';
            }

            if (btnSimpan) {
                btnSimpan.disabled = false;
                btnSimpan.title = '';
            }
            return;
        }

        // Indikator loading pemeriksaan jadwal
        barStatus.style.background = '#f8fafc';
        barStatus.style.borderColor = '#cbd5e1';
        barStatus.style.color = '#334155';
        if (iconStatus) iconStatus.className = 'spinner-border spinner-border-sm text-primary flex-shrink-0';
        if (textStatus) textStatus.textContent = 'Memeriksa ketersediaan meja pada jadwal yang dipilih...';

        if (feedbackJadwal) {
            feedbackJadwal.className = 'form-text text-primary';
            feedbackJadwal.innerHTML = '<span class="spinner-border spinner-border-sm me-1" style="width: 11px; height: 11px;"></span> Memeriksa jadwal meja...';
        }

        clearTimeout(timerCekBentrok);
        timerCekBentrok = setTimeout(() => {
            const waktuParam = encodeURIComponent(`${tglVal} ${jamVal}:00`);
            fetch(`pemesanan.php?aksi=cek_ketersediaan&meja_id=${mejaId}&jumlah_tamu=${jmlTamu}&waktu_pesan=${waktuParam}`)
                .then(res => res.json())
                .then(data => {
                    if (data.bentrok) {
                        // Jadwal Bentrok
                        barStatus.style.background = '#fffbeb';
                        barStatus.style.borderColor = '#fde68a';
                        barStatus.style.color = '#92400e';
                        if (iconStatus) iconStatus.className = 'bi bi-calendar-x-fill text-warning fs-6 flex-shrink-0';
                        if (textStatus) {
                            textStatus.innerHTML = `Meja ${nomorMeja} sudah memiliki reservasi pada jam tersebut. Silakan pilih meja lain atau sesuaikan jam reservasi.`;
                        }

                        if (feedbackJadwal) {
                            feedbackJadwal.className = 'form-text text-danger fw-semibold';
                            feedbackJadwal.innerHTML = `<i class="bi bi-x-circle-fill me-1"></i> Jadwal bentrok (${data.bentrok_pesan || 'Meja telah terisi'})`;
                        }

                        if (btnSimpan) {
                            btnSimpan.disabled = true;
                            btnSimpan.title = 'Jadwal reservasi bentrok dengan pesanan lain';
                        }
                    } else {
                        // Ketersediaan Aman
                        barStatus.style.background = '#f0fdf4';
                        barStatus.style.borderColor = '#bbf7d0';
                        barStatus.style.color = '#166534';
                        if (iconStatus) iconStatus.className = 'bi bi-check-circle-fill text-success fs-6 flex-shrink-0';
                        if (textStatus) {
                            textStatus.innerHTML = `Meja ${nomorMeja} tersedia dan kapasitas mencukupi. Reservasi siap disimpan.`;
                        }

                        if (feedbackJadwal) {
                            feedbackJadwal.className = 'form-text text-success fw-medium';
                            feedbackJadwal.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Meja kosong dan siap digunakan pada jam ini.';
                        }

                        if (btnSimpan) {
                            btnSimpan.disabled = false;
                            btnSimpan.title = '';
                        }
                    }
                })
                .catch(() => {
                    barStatus.style.background = '#f8fafc';
                    barStatus.style.borderColor = '#e2e8f0';
                    barStatus.style.color = '#475569';
                    if (iconStatus) iconStatus.className = 'bi bi-check-circle text-muted fs-6 flex-shrink-0';
                    if (textStatus) textStatus.textContent = 'Kapasitas sesuai. Verifikasi jadwal akan diproses saat disimpan.';
                    if (btnSimpan) btnSimpan.disabled = false;
                });
        }, 200);
    }

    // Tangani submit form jika kapasitas melebihi batas (halus tanpa alert popup yang mengganggu)
    const formPemesanan = document.getElementById('formPemesanan');
    if (formPemesanan) {
        formPemesanan.addEventListener('submit', function(e) {
            const selectedOpt = mejaSelect ? mejaSelect.options[mejaSelect.selectedIndex] : null;
            const kapasitasMeja = selectedOpt ? parseInt(selectedOpt.getAttribute('data-kapasitas') || '0', 10) : 0;
            const jmlTamu = guestInput ? parseInt(guestInput.value, 10) || 0 : 0;

            if (kapasitasMeja > 0 && jmlTamu > kapasitasMeja) {
                e.preventDefault();
                const wrapperGuest = document.getElementById('wrapperGuestCounter');
                if (wrapperGuest) {
                    wrapperGuest.classList.add('shake-feedback');
                    setTimeout(() => wrapperGuest.classList.remove('shake-feedback'), 400);
                }
                const barStatus = document.getElementById('barStatusReservasi');
                if (barStatus) {
                    barStatus.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
                guestInput.focus();
                return false;
            }
        });
    }

    // Modal Events: Jalankan pemeriksaan saat modal dibuka
    const modalPemesananEl = document.getElementById('modalPemesanan');
    if (modalPemesananEl) {
        modalPemesananEl.addEventListener('shown.bs.modal', function() {
            periksaAlurReservasi();
        });
    }

    // =========================================================================
    // DRAFT PERSISTENCE (Jika Submit Sebelumnya Error, Modal Otomatis Terbuka)
    // Draft lama yang salah TETAP ADA dan TIDAK HILANG untuk dikoreksi pengguna!
    // =========================================================================
    <?php if (!empty($draftPemesanan)): ?>
    if (modalPemesananEl && typeof bootstrap !== 'undefined') {
        const modalDraftInstance = bootstrap.Modal.getOrCreateInstance(modalPemesananEl);
        modalDraftInstance.show();
        setTimeout(periksaAlurReservasi, 150);
    }
    <?php endif; ?>
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
