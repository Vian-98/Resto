<?php
/**
 * Class Abstrak BaseModel
 * Unit Kompetensi:
 * - J.620100.018.02 (OOP: Abstract Class, Inheritance, Hak Akses protected)
 * - J.620100.021.02 (Akses Basis Data: Operasi Dasar CRUD)
 */

declare(strict_types=1);

namespace App;

use PDO;
use PDOException;

abstract class BaseModel
{
    /**
     * @var PDO Objek koneksi basis data
     */
    protected PDO $db;

    /**
     * @var string Nama tabel terkait pada database
     */
    protected string $tabel = '';

    /**
     * @var int|null ID record
     */
    protected ?int $id = null;

    /**
     * Constructor BaseModel
     */
    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getInstance()->getConnection();
    }

    /**
     * Mengambil ID record saat ini
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Mengatur ID record
     */
    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    /**
     * Mengambil semua record dari tabel
     *
     * @return array
     */
    public function semua(): array
    {
        try {
            $stmt = $this->db->query("SELECT * FROM {$this->tabel} ORDER BY id DESC");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            catat_log("Gagal membaca semua data [{$this->tabel}]: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }

    /**
     * Mencari satu record berdasarkan primary key ID
     *
     * @param int $id
     * @return array|null
     */
    public function cari(int $id): ?array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM {$this->tabel} WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $id]);
            $data = $stmt->fetch();
            return $data ?: null;
        } catch (PDOException $e) {
            catat_log("Gagal mencari data [{$this->tabel}] ID {$id}: " . $e->getMessage(), 'ERROR');
            return null;
        }
    }

    /**
     * Menghapus record berdasarkan ID
     *
     * @param int $id
     * @return bool
     */
    public function hapus(int $id): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM {$this->tabel} WHERE id = :id");
            return $stmt->execute(['id' => $id]);
        } catch (PDOException $e) {
            catat_log("Gagal menghapus data [{$this->tabel}] ID {$id}: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }

    /**
     * Method abstrak untuk menyimpan record (Insert jika ID null, Update jika ada ID)
     * Setiap turunan class wajib meng-override method ini (Polymorphism).
     *
     * @return bool
     */
    abstract public function simpan(): bool;

    /**
     * Method abstrak untuk memvalidasi atribut objek sebelum disimpan
     *
     * @return bool
     */
    abstract public function validasi(): bool;
}
