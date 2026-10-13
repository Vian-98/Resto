<?php
/**
 * Class Database
 * Unit Kompetensi:
 * - J.620100.018.02 (OOP: Encapsulation & Hak Akses private/public)
 * - J.620100.021.02 (Akses Basis Data: Koneksi PDO)
 * - J.620100.017.02 (Penanganan Error try-catch)
 */

declare(strict_types=1);

namespace App;

use PDO;
use PDOException;

class Database
{
    /**
     * @var PDO|null Instance koneksi PDO
     */
    private ?PDO $pdo = null;

    /**
     * @var string Host database
     */
    private string $host;

    /**
     * @var string Nama database
     */
    private string $dbName;

    /**
     * @var string Username database
     */
    private string $user;

    /**
     * @var string Password database
     */
    private string $pass;

    /**
     * @var int Port database
     */
    private int $port;

    /**
     * @var Database|null Instance singleton
     */
    private static ?Database $instance = null;

    /**
     * Constructor dengan enkapsulasi parameter koneksi
     */
    public function __construct(
        string $host = '127.0.0.1',
        string $dbName = 'db_restoran',
        string $user = 'root',
        string $pass = '',
        int $port = 3306
    ) {
        $this->host = $host;
        $this->dbName = $dbName;
        $this->user = $user;
        $this->pass = $pass;
        $this->port = $port;

        $this->connect();
    }

    /**
     * Singleton instance untuk penggunaan global aplikasi
     *
     * @return Database
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Inisialisasi koneksi PDO dengan try-catch
     *
     * @throws PDOException jika koneksi gagal
     */
    private function connect(): void
    {
        $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->dbName};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $this->user, $this->pass, $options);
            // Sinkronisasi zona waktu session MySQL dengan WIB (+07:00)
            $this->pdo->exec("SET time_zone = '+07:00'");
        } catch (PDOException $e) {
            catat_log("Gagal koneksi database: " . $e->getMessage(), 'ERROR');
            throw new PDOException("Koneksi basis data gagal: " . $e->getMessage());
        }
    }

    /**
     * Mengambil objek PDO aktif
     *
     * @return PDO
     */
    public function getConnection(): PDO
    {
        return $this->pdo;
    }
}
