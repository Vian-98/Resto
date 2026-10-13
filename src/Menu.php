<?php
/**
 * Class Menu
 * Unit Kompetensi:
 * - J.620100.018.02 (OOP: Inheritance, Overriding, Encapsulation)
 * - J.620100.021.02 (Akses Basis Data: CRUD Menu)
 */

declare(strict_types=1);

namespace App;

use PDO;
use PDOException;

class Menu extends BaseModel
{
    protected string $tabel = 'menu';

    /**
     * @var string Nama menu makanan/minuman
     */
    private string $nama = '';

    /**
     * @var float Harga per porsi
     */
    private float $harga = 0.0;

    public function __construct(string $nama = '', float $harga = 0.0, ?int $id = null, ?PDO $db = null)
    {
        parent::__construct($db);
        $this->nama = $nama;
        $this->harga = $harga;
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

    public function getHarga(): float
    {
        return $this->harga;
    }

    public function setHarga(float $harga): void
    {
        $this->harga = max(0.0, $harga);
    }

    /**
     * Override validasi
     */
    public function validasi(): bool
    {
        if (empty($this->nama) || strlen($this->nama) < 2) {
            return false;
        }

        if ($this->harga <= 0) {
            return false;
        }

        return true;
    }

    /**
     * Override simpan dari BaseModel
     */
    public function simpan(): bool
    {
        if (!$this->validasi()) {
            return false;
        }

        try {
            if ($this->id === null) {
                $sql = "INSERT INTO {$this->tabel} (nama, harga) VALUES (:nama, :harga)";
                $stmt = $this->db->prepare($sql);
                $sukses = $stmt->execute([
                    'nama' => $this->nama,
                    'harga' => $this->harga,
                ]);

                if ($sukses) {
                    $this->id = (int)$this->db->lastInsertId();
                }
                return $sukses;
            } else {
                $sql = "UPDATE {$this->tabel} SET nama = :nama, harga = :harga WHERE id = :id";
                $stmt = $this->db->prepare($sql);
                return $stmt->execute([
                    'id' => $this->id,
                    'nama' => $this->nama,
                    'harga' => $this->harga,
                ]);
            }
        } catch (PDOException $e) {
            catat_log("Gagal menyimpan Menu: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
}
