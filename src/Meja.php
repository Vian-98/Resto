<?php
/**
 * Class Meja
 * Unit Kompetensi:
 * - J.620100.018.02 (OOP: Inheritance, Overriding, Encapsulation)
 * - J.620100.021.02 (Akses Basis Data: CRUD Meja)
 */

declare(strict_types=1);

namespace App;

use PDO;
use PDOException;

class Meja extends BaseModel
{
    protected string $tabel = 'meja';

    /**
     * @var int Nomor meja unik
     */
    private int $nomor = 0;

    /**
     * @var int Kapasitas maksimum meja
     */
    private int $kapasitas = 0;

    public function __construct(int $nomor = 0, int $kapasitas = 0, ?int $id = null, ?PDO $db = null)
    {
        parent::__construct($db);
        $this->nomor = $nomor;
        $this->kapasitas = $kapasitas;
        $this->id = $id;
    }

    // Getter dan Setter
    public function getNomor(): int
    {
        return $this->nomor;
    }

    public function setNomor(int $nomor): void
    {
        $this->nomor = $nomor;
    }

    public function getKapasitas(): int
    {
        return $this->kapasitas;
    }

    public function setKapasitas(int $kapasitas): void
    {
        $this->kapasitas = $kapasitas;
    }

    /**
     * Cek keunikan nomor meja
     */
    public function cekNomorUnik(int $nomor, ?int $abaikanId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM {$this->tabel} WHERE nomor = :nomor";
        $params = ['nomor' => $nomor];

        if ($abaikanId !== null) {
            $sql .= " AND id != :id";
            $params['id'] = $abaikanId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return ((int)$stmt->fetchColumn()) === 0;
    }

    /**
     * Override validasi
     */
    public function validasi(): bool
    {
        if ($this->nomor <= 0 || $this->kapasitas <= 0) {
            return false;
        }

        if (!$this->cekNomorUnik($this->nomor, $this->id)) {
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
                $sql = "INSERT INTO {$this->tabel} (nomor, kapasitas) VALUES (:nomor, :kapasitas)";
                $stmt = $this->db->prepare($sql);
                $sukses = $stmt->execute([
                    'nomor' => $this->nomor,
                    'kapasitas' => $this->kapasitas,
                ]);

                if ($sukses) {
                    $this->id = (int)$this->db->lastInsertId();
                }
                return $sukses;
            } else {
                $sql = "UPDATE {$this->tabel} SET nomor = :nomor, kapasitas = :kapasitas WHERE id = :id";
                $stmt = $this->db->prepare($sql);
                return $stmt->execute([
                    'id' => $this->id,
                    'nomor' => $this->nomor,
                    'kapasitas' => $this->kapasitas,
                ]);
            }
        } catch (PDOException $e) {
            catat_log("Gagal menyimpan Meja: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
}
