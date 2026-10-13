<?php
/**
 * Class Pelanggan
 * Unit Kompetensi:
 * - J.620100.018.02 (OOP: Inheritance, Overriding, Encapsulation)
 * - J.620100.021.02 (Akses Basis Data: CRUD Pelanggan)
 */

declare(strict_types=1);

namespace App;

use PDO;
use PDOException;

class Pelanggan extends BaseModel
{
    protected string $tabel = 'pelanggan';

    /**
     * @var string Nama pelanggan (encapsulation)
     */
    private string $nama = '';

    /**
     * @var string Nomor telepon pelanggan (encapsulation)
     */
    private string $telepon = '';

    public function __construct(string $nama = '', string $telepon = '', ?int $id = null, ?PDO $db = null)
    {
        parent::__construct($db);
        $this->nama = $nama;
        $this->telepon = $telepon;
        $this->id = $id;
    }

    // Getter dan Setter
    public function getNama(): string
    {
        return $this->nama;
    }

    public function setNama(string $nama): void
    {
        $this->nama = trim($nama);
    }

    public function getTelepon(): string
    {
        return $this->telepon;
    }

    public function setTelepon(string $telepon): void
    {
        $this->telepon = trim($telepon);
    }

    /**
     * Override validasi dari BaseModel
     */
    public function validasi(): bool
    {
        if (empty($this->nama) || empty($this->telepon)) {
            return false;
        }

        // Minimal panjang nama 2 karakter
        if (strlen($this->nama) < 2) {
            return false;
        }

        return true;
    }

    /**
     * Override simpan dari BaseModel (Polymorphism & Overriding)
     */
    public function simpan(): bool
    {
        if (!$this->validasi()) {
            return false;
        }

        try {
            if ($this->id === null) {
                // Operasi INSERT
                $sql = "INSERT INTO {$this->tabel} (nama, telepon) VALUES (:nama, :telepon)";
                $stmt = $this->db->prepare($sql);
                $sukses = $stmt->execute([
                    'nama' => $this->nama,
                    'telepon' => $this->telepon,
                ]);

                if ($sukses) {
                    $this->id = (int)$this->db->lastInsertId();
                }
                return $sukses;
            } else {
                // Operasi UPDATE
                $sql = "UPDATE {$this->tabel} SET nama = :nama, telepon = :telepon WHERE id = :id";
                $stmt = $this->db->prepare($sql);
                return $stmt->execute([
                    'id' => $this->id,
                    'nama' => $this->nama,
                    'telepon' => $this->telepon,
                ]);
            }
        } catch (PDOException $e) {
            catat_log("Gagal menyimpan Pelanggan: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
}
