<?php
/**
 * Class Pemesanan
 * Unit Kompetensi:
 * - J.620100.018.02 (OOP: Encapsulation dengan protected, getter/setter, method logic)
 * - J.620100.021.02 (Akses Basis Data: Relasi Foreign Key, Transaksi & Query JOIN)
 * - J.620100.017.02 (Pemrograman Terstruktur: percabangan & perulangan perhitungan)
 */

declare(strict_types=1);

namespace App;

use PDO;
use PDOException;
use InvalidArgumentException;

class Pemesanan extends BaseModel
{
    protected string $tabel = 'pemesanan';

    /**
     * Properti dengan hak akses protected (Encapsulation)
     */
    protected int $pelangganId = 0;
    protected int $mejaId = 0;
    protected string $waktuPesan = '';
    protected int $jumlahTamu = 0;
    protected string $status = 'Dipesan';
    protected float $total = 0.0;

    /**
     * @var array Daftar item pesanan: [['menu_id' => int, 'jumlah' => int, 'harga' => float]]
     */
    protected array $items = [];

    public function __construct(
        int $pelangganId = 0,
        int $mejaId = 0,
        string $waktuPesan = '',
        int $jumlahTamu = 0,
        string $status = 'Dipesan',
        ?int $id = null,
        ?PDO $db = null
    ) {
        parent::__construct($db);
        $this->pelangganId = $pelangganId;
        $this->mejaId = $mejaId;
        $this->waktuPesan = $waktuPesan;
        $this->jumlahTamu = $jumlahTamu;
        $this->status = $status;
        $this->id = $id;
    }

    // Getter dan Setter
    public function getPelangganId(): int
    {
        return $this->pelangganId;
    }

    public function setPelangganId(int $pelangganId): void
    {
        $this->pelangganId = $pelangganId;
    }

    public function getMejaId(): int
    {
        return $this->mejaId;
    }

    public function setMejaId(int $mejaId): void
    {
        $this->mejaId = $mejaId;
    }

    public function getWaktuPesan(): string
    {
        return $this->waktuPesan;
    }

    public function setWaktuPesan(string $waktuPesan): void
    {
        $this->waktuPesan = trim($waktuPesan);
    }

    public function getJumlahTamu(): int
    {
        return $this->jumlahTamu;
    }

    public function setJumlahTamu(int $jumlahTamu): void
    {
        $this->jumlahTamu = $jumlahTamu;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getTotal(): float
    {
        return $this->total;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * Menambahkan item menu ke dalam pesanan
     *
     * @param int $menuId
     * @param int $jumlah
     * @param float $harga
     * @return void
     */
    public function tambahItem(int $menuId, int $jumlah, float $harga): void
    {
        if ($menuId <= 0 || $jumlah <= 0 || $harga < 0) {
            throw new InvalidArgumentException("Data item tidak valid");
        }

        foreach ($this->items as &$existingItem) {
            if ($existingItem['menu_id'] === $menuId) {
                $existingItem['jumlah'] += $jumlah;
                $existingItem['subtotal'] = $existingItem['jumlah'] * $harga;
                $this->hitungTotal();
                return;
            }
        }
        unset($existingItem);

        $this->items[] = [
            'menu_id' => $menuId,
            'jumlah' => $jumlah,
            'harga' => $harga,
            'subtotal' => $jumlah * $harga,
        ];

        $this->hitungTotal();
    }

    /**
     * Menghitung total biaya dari semua item pesanan (Unit Test T-01, T-02)
     * Menggunakan perulangan foreach terstruktur
     *
     * @return float
     */
    public function hitungTotal(): float
    {
        // Edge case: tanpa item pesanan (T-02)
        if (empty($this->items)) {
            $this->total = 0.0;
            return 0.0;
        }

        $akumulasi = 0.0;
        // Kasus normal: iterasi item menu (T-01)
        foreach ($this->items as $item) {
            $akumulasi += ($item['jumlah'] * $item['harga']);
        }

        $this->total = (float)$akumulasi;
        return $this->total;
    }

    /**
     * Validasi bentrok jadwal meja (Unit Test T-03, T-04)
     * Mengembalikan true jika jadwal BENTROK (sudah dipesan), false jika AMAN
     *
     * @param int $mejaId
     * @param string $waktuPesan format YYYY-MM-DD HH:MM[:SS]
     * @param int|null $abaikanId
     * @return bool
     */
    public function cekBentrok(int $mejaId, string $waktuPesan, ?int $abaikanId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM {$this->tabel} 
                WHERE meja_id = :meja_id 
                AND waktu_pesan = :waktu_pesan 
                AND status != 'Dibatalkan'";

        $params = [
            'meja_id' => $mejaId,
            'waktu_pesan' => $waktuPesan,
        ];

        if ($abaikanId !== null) {
            $sql .= " AND id != :abaikan_id";
            $params['abaikan_id'] = $abaikanId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return ((int)$stmt->fetchColumn()) > 0;
    }

    /**
     * Validasi kapasitas meja terhadap jumlah tamu (Unit Test T-05)
     *
     * @param int $mejaId
     * @param int $jumlahTamu
     * @return bool True jika memenuhi, False jika melebihi kapasitas
     */
    public function validasiKapasitas(int $mejaId, int $jumlahTamu): bool
    {
        if ($jumlahTamu <= 0) {
            return false;
        }

        $stmt = $this->db->prepare("SELECT kapasitas FROM meja WHERE id = :id");
        $stmt->execute(['id' => $mejaId]);
        $kapasitas = $stmt->fetchColumn();

        if ($kapasitas === false) {
            return false; // Meja tidak ditemukan
        }

        return $jumlahTamu <= (int)$kapasitas;
    }

    /**
     * Override validasi model
     */
    public function validasi(): bool
    {
        if ($this->pelangganId <= 0 || $this->mejaId <= 0 || empty($this->waktuPesan)) {
            return false;
        }

        // Cek kapasitas
        if (!$this->validasiKapasitas($this->mejaId, $this->jumlahTamu)) {
            return false;
        }

        // Cek bentrok jadwal
        if ($this->cekBentrok($this->mejaId, $this->waktuPesan, $this->id)) {
            return false;
        }

        return true;
    }

    /**
     * Override simpan pemesanan ke database
     */
    public function simpan(): bool
    {
        if (!$this->validasi()) {
            return false;
        }

        try {
            if ($this->id === null) {
                $sql = "INSERT INTO {$this->tabel} (pelanggan_id, meja_id, waktu_pesan, jumlah_tamu, status, total) 
                        VALUES (:pelanggan_id, :meja_id, :waktu_pesan, :jumlah_tamu, :status, :total)";
                $stmt = $this->db->prepare($sql);
                $sukses = $stmt->execute([
                    'pelanggan_id' => $this->pelangganId,
                    'meja_id' => $this->mejaId,
                    'waktu_pesan' => $this->waktuPesan,
                    'jumlah_tamu' => $this->jumlahTamu,
                    'status' => $this->status,
                    'total' => $this->total,
                ]);

                if ($sukses) {
                    $this->id = (int)$this->db->lastInsertId();
                }
                return $sukses;
            } else {
                $sql = "UPDATE {$this->tabel} 
                        SET pelanggan_id = :pelanggan_id, meja_id = :meja_id, waktu_pesan = :waktu_pesan, 
                            jumlah_tamu = :jumlah_tamu, status = :status, total = :total 
                        WHERE id = :id";
                $stmt = $this->db->prepare($sql);
                return $stmt->execute([
                    'id' => $this->id,
                    'pelanggan_id' => $this->pelangganId,
                    'meja_id' => $this->mejaId,
                    'waktu_pesan' => $this->waktuPesan,
                    'jumlah_tamu' => $this->jumlahTamu,
                    'status' => $this->status,
                    'total' => $this->total,
                ]);
            }
        } catch (PDOException $e) {
            catat_log("Gagal menyimpan Pemesanan: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }

    /**
     * Mengubah status pemesanan (F-07)
     */
    public function ubahStatus(int $id, string $status): bool
    {
        $statusValid = ['Dipesan', 'Selesai', 'Dibatalkan'];
        if (!in_array($status, $statusValid, true)) {
            return false;
        }

        try {
            $stmt = $this->db->prepare("UPDATE {$this->tabel} SET status = :status WHERE id = :id");
            return $stmt->execute(['status' => $status, 'id' => $id]);
        } catch (PDOException $e) {
            catat_log("Gagal ubah status pemesanan: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }

    /**
     * Membaca semua data pemesanan beserta informasi relasi pelanggan dan meja
     *
     * @return array
     */
    public function semuaDenganRelasi(): array
    {
        try {
            $sql = "SELECT p.*, pel.nama AS nama_pelanggan, pel.telepon, m.nomor AS nomor_meja, m.kapasitas 
                    FROM {$this->tabel} p 
                    JOIN pelanggan pel ON p.pelanggan_id = pel.id 
                    JOIN meja m ON p.meja_id = m.id 
                    ORDER BY p.waktu_pesan DESC";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            catat_log("Gagal membaca pemesanan relasi: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }

    /**
     * Membaca detail satu pemesanan lengkap dengan relasi
     */
    public function cariDenganRelasi(int $id): ?array
    {
        try {
            $sql = "SELECT p.*, pel.nama AS nama_pelanggan, pel.telepon, m.nomor AS nomor_meja, m.kapasitas 
                    FROM {$this->tabel} p 
                    JOIN pelanggan pel ON p.pelanggan_id = pel.id 
                    JOIN meja m ON p.meja_id = m.id 
                    WHERE p.id = :id LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (PDOException $e) {
            catat_log("Gagal membaca pemesanan ID {$id}: " . $e->getMessage(), 'ERROR');
            return null;
        }
    }

    /**
     * Simpan item detail pesanan ke tabel detail_pesanan dan update total pemesanan.
     * Jika menu yang dipilih sudah ada dalam pesanan ini, kuantitas akan otomatis digabungkan (di-merge).
     */
    public function simpanItemPesanan(int $pemesananId, int $menuId, int $jumlah): bool
    {
        try {
            // Ambil harga menu
            $stmtMenu = $this->db->prepare("SELECT harga FROM menu WHERE id = :id");
            $stmtMenu->execute(['id' => $menuId]);
            $harga = (float)$stmtMenu->fetchColumn();

            if ($harga <= 0) {
                return false;
            }

            // Cek apakah item menu ini sudah pernah dipesan pada pemesanan ini
            $stmtCek = $this->db->prepare("SELECT id, jumlah FROM detail_pesanan WHERE pemesanan_id = :pemesanan_id AND menu_id = :menu_id LIMIT 1");
            $stmtCek->execute(['pemesanan_id' => $pemesananId, 'menu_id' => $menuId]);
            $existing = $stmtCek->fetch();

            if ($existing) {
                // Gabungkan kuantitas jika sudah ada
                $jumlahBaru = (int)$existing['jumlah'] + $jumlah;
                $subtotalBaru = $jumlahBaru * $harga;
                $stmtUpdate = $this->db->prepare("UPDATE detail_pesanan SET jumlah = :jumlah, subtotal = :subtotal WHERE id = :id");
                $sukses = $stmtUpdate->execute([
                    'jumlah' => $jumlahBaru,
                    'subtotal' => $subtotalBaru,
                    'id' => (int)$existing['id'],
                ]);
            } else {
                // Insert baru
                $subtotal = $jumlah * $harga;
                $sql = "INSERT INTO detail_pesanan (pemesanan_id, menu_id, jumlah, subtotal) 
                        VALUES (:pemesanan_id, :menu_id, :jumlah, :subtotal)";
                $stmt = $this->db->prepare($sql);
                $sukses = $stmt->execute([
                    'pemesanan_id' => $pemesananId,
                    'menu_id' => $menuId,
                    'jumlah' => $jumlah,
                    'subtotal' => $subtotal,
                ]);
            }

            if ($sukses) {
                // Perbarui total pemesanan
                $this->updateTotalPemesanan($pemesananId);
            }
            return $sukses;
        } catch (PDOException $e) {
            catat_log("Gagal simpan item detail pesanan: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }

    /**
     * Hitung ulang total pemesanan dari detail_pesanan
     */
    public function updateTotalPemesanan(int $pemesananId): void
    {
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(subtotal), 0) FROM detail_pesanan WHERE pemesanan_id = :id");
        $stmt->execute(['id' => $pemesananId]);
        $totalBaru = (float)$stmt->fetchColumn();

        $stmtUpdate = $this->db->prepare("UPDATE {$this->tabel} SET total = :total WHERE id = :id");
        $stmtUpdate->execute(['total' => $totalBaru, 'id' => $pemesananId]);
    }

    /**
     * Ubah jumlah satu item menu (tambah / kurang). Jika jumlah <= 0, item otomatis dihapus.
     */
    public function ubahJumlahItem(int $detailId, int $pemesananId, int $jumlahBaru): bool
    {
        try {
            if ($jumlahBaru <= 0) {
                return $this->hapusItemDetail($detailId, $pemesananId);
            }

            // Ambil harga menu untuk hitung ulang subtotal
            $stmt = $this->db->prepare("SELECT dp.menu_id, m.harga 
                                        FROM detail_pesanan dp 
                                        JOIN menu m ON dp.menu_id = m.id 
                                        WHERE dp.id = :id AND dp.pemesanan_id = :pemesanan_id");
            $stmt->execute(['id' => $detailId, 'pemesanan_id' => $pemesananId]);
            $row = $stmt->fetch();
            if (!$row) {
                return false;
            }

            $harga = (float)$row['harga'];
            $subtotalBaru = $jumlahBaru * $harga;

            $stmtUpdate = $this->db->prepare("UPDATE detail_pesanan SET jumlah = :jumlah, subtotal = :subtotal WHERE id = :id AND pemesanan_id = :pemesanan_id");
            $sukses = $stmtUpdate->execute([
                'jumlah' => $jumlahBaru,
                'subtotal' => $subtotalBaru,
                'id' => $detailId,
                'pemesanan_id' => $pemesananId,
            ]);

            if ($sukses) {
                $this->updateTotalPemesanan($pemesananId);
            }
            return $sukses;
        } catch (PDOException $e) {
            catat_log("Gagal ubah jumlah item: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }

    /**
     * Konsolidasi item duplikat dalam pemesanan jika ada data historis yang terpisah
     */
    public function konsolidasiItemPesanan(int $pemesananId): void
    {
        try {
            $sql = "SELECT menu_id, COUNT(*) as jml_baris, SUM(jumlah) as total_qty, MIN(id) as id_pertama 
                    FROM detail_pesanan 
                    WHERE pemesanan_id = :pemesanan_id 
                    GROUP BY menu_id 
                    HAVING COUNT(*) > 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['pemesanan_id' => $pemesananId]);
            $duplikatList = $stmt->fetchAll();

            foreach ($duplikatList as $dup) {
                $menuId = (int)$dup['menu_id'];
                $totalQty = (int)$dup['total_qty'];
                $idPertama = (int)$dup['id_pertama'];

                $stmtHarga = $this->db->prepare("SELECT harga FROM menu WHERE id = :id");
                $stmtHarga->execute(['id' => $menuId]);
                $harga = (float)$stmtHarga->fetchColumn();

                $subtotal = $totalQty * $harga;

                $stmtUp = $this->db->prepare("UPDATE detail_pesanan SET jumlah = :jumlah, subtotal = :subtotal WHERE id = :id");
                $stmtUp->execute([
                    'jumlah' => $totalQty,
                    'subtotal' => $subtotal,
                    'id' => $idPertama,
                ]);

                $stmtDel = $this->db->prepare("DELETE FROM detail_pesanan WHERE pemesanan_id = :pemesanan_id AND menu_id = :menu_id AND id != :id_pertama");
                $stmtDel->execute([
                    'pemesanan_id' => $pemesananId,
                    'menu_id' => $menuId,
                    'id_pertama' => $idPertama,
                ]);
            }

            if (!empty($duplikatList)) {
                $this->updateTotalPemesanan($pemesananId);
            }
        } catch (PDOException $e) {
            catat_log("Gagal konsolidasi item pesanan: " . $e->getMessage(), 'ERROR');
        }
    }

    /**
     * Ambil daftar item dari detail_pesanan untuk pemesanan tertentu
     */
    public function ambilItemsDetail(int $pemesananId): array
    {
        try {
            // Otomatis bersihkan duplikat jika ada baris terpisah untuk menu yang sama
            $this->konsolidasiItemPesanan($pemesananId);

            $sql = "SELECT dp.*, m.nama AS nama_menu, m.harga 
                    FROM detail_pesanan dp 
                    JOIN menu m ON dp.menu_id = m.id 
                    WHERE dp.pemesanan_id = :pemesanan_id 
                    ORDER BY dp.id ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['pemesanan_id' => $pemesananId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            catat_log("Gagal mengambil detail item: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }

    /**
     * Hapus satu item detail pesanan
     */
    public function hapusItemDetail(int $detailId, int $pemesananId): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM detail_pesanan WHERE id = :id AND pemesanan_id = :pemesanan_id");
            $sukses = $stmt->execute(['id' => $detailId, 'pemesanan_id' => $pemesananId]);
            if ($sukses) {
                $this->updateTotalPemesanan($pemesananId);
            }
            return $sukses;
        } catch (PDOException $e) {
            catat_log("Gagal hapus item detail: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
}
