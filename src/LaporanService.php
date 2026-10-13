<?php
/**
 * Class LaporanService
 * Unit Kompetensi:
 * - J.620100.018.02 (OOP: Single Responsibility Principle)
 * - J.620100.021.02 (Akses Basis Data: Query Multi-tabel JOIN, Agregasi SUM/COUNT)
 * - J.620100.019.02 (Menggunakan Library Pre-existing: Dompdf)
 */

declare(strict_types=1);

namespace App;

use Dompdf\Dompdf;
use Dompdf\Options;
use PDO;
use PDOException;

class LaporanService
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getInstance()->getConnection();
    }

    /**
     * Mengambil daftar pemesanan berdasarkan rentang tanggal
     *
     * @param string $tanggalMulai Format YYYY-MM-DD
     * @param string $tanggalSelesai Format YYYY-MM-DD
     * @return array
     */
    public function getLaporanRentang(string $tanggalMulai, string $tanggalSelesai): array
    {
        try {
            $sql = "SELECT p.*, pel.nama AS nama_pelanggan, pel.telepon, m.nomor AS nomor_meja 
                    FROM pemesanan p 
                    JOIN pelanggan pel ON p.pelanggan_id = pel.id 
                    JOIN meja m ON p.meja_id = m.id 
                    WHERE DATE(p.waktu_pesan) BETWEEN :mulai AND :selesai 
                    ORDER BY p.waktu_pesan ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'mulai' => $tanggalMulai,
                'selesai' => $tanggalSelesai,
            ]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            catat_log("Gagal mengambil laporan rentang: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }

    /**
     * Alias untuk getLaporanRentang()
     */
    public function getLaporan(string $tanggalMulai, string $tanggalSelesai): array
    {
        return $this->getLaporanRentang($tanggalMulai, $tanggalSelesai);
    }

    /**
     * Menghitung ringkasan statistik pemesanan (total transaksi & total pendapatan)
     *
     * @param string $tanggalMulai
     * @param string $tanggalSelesai
     * @return array ['total_transaksi' => int, 'total_pendapatan' => float]
     */
    public function getRingkasan(string $tanggalMulai, string $tanggalSelesai): array
    {
        try {
            $sql = "SELECT 
                        COUNT(*) AS total_transaksi, 
                        COALESCE(SUM(CASE WHEN status != 'Dibatalkan' THEN total ELSE 0 END), 0) AS total_pendapatan 
                    FROM pemesanan 
                    WHERE DATE(waktu_pesan) BETWEEN :mulai AND :selesai";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'mulai' => $tanggalMulai,
                'selesai' => $tanggalSelesai,
            ]);
            $res = $stmt->fetch();
            return [
                'total_transaksi' => (int)($res['total_transaksi'] ?? 0),
                'total_pendapatan' => (float)($res['total_pendapatan'] ?? 0),
            ];
        } catch (PDOException $e) {
            catat_log("Gagal menghitung ringkasan laporan: " . $e->getMessage(), 'ERROR');
            return ['total_transaksi' => 0, 'total_pendapatan' => 0.0];
        }
    }

    /**
     * Menghasilkan dokumen PDF laporan pemesanan menggunakan Dompdf (Unit 019)
     *
     * @param string $tanggalMulai
     * @param string $tanggalSelesai
     * @param bool $streamLangsung Jika true langsung download ke browser
     * @return string Konten binary PDF
     */
    public function cetakPDF(string $tanggalMulai, string $tanggalSelesai, bool $streamLangsung = true): string
    {
        $daftarPemesanan = $this->getLaporanRentang($tanggalMulai, $tanggalSelesai);
        $ringkasan = $this->getRingkasan($tanggalMulai, $tanggalSelesai);

        // Buat template HTML untuk PDF
        $barisTabel = '';
        $no = 1;
        foreach ($daftarPemesanan as $row) {
            $formatTotal = format_rupiah((float)$row['total']);
            $waktuFormatted = date('d/m/Y H:i', strtotime($row['waktu_pesan']));
            $barisTabel .= "<tr>
                <td style='text-align: center;'>{$no}</td>
                <td>{$row['nama_pelanggan']}</td>
                <td style='text-align: center;'>Meja {$row['nomor_meja']}</td>
                <td style='text-align: center;'>{$waktuFormatted}</td>
                <td style='text-align: center;'>{$row['jumlah_tamu']} orang</td>
                <td style='text-align: center;'>{$row['status']}</td>
                <td style='text-align: right;'>{$formatTotal}</td>
            </tr>";
            $no++;
        }

        if (empty($barisTabel)) {
            $barisTabel = "<tr><td colspan='7' style='text-align:center;'>Tidak ada data pada periode ini</td></tr>";
        }

        $totalPendapatanFormatted = format_rupiah($ringkasan['total_pendapatan']);

        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <title>Laporan Pemesanan Restoran</title>
            <style>
                body { font-family: sans-serif; font-size: 12px; color: #333; margin: 20px; }
                .header { text-align: center; border-bottom: 2px solid #2c3e50; padding-bottom: 10px; margin-bottom: 20px; }
                .header h1 { margin: 0; font-size: 20px; color: #2c3e50; }
                .header p { margin: 5px 0 0; color: #7f8c8d; }
                table { width: 100%; border-collapse: collapse; margin-top: 15px; }
                th, td { border: 1px solid #bdc3c7; padding: 8px; }
                th { background-color: #ecf0f1; text-align: left; }
                .ringkasan { margin-top: 20px; width: 50%; float: right; }
                .ringkasan td { border: none; padding: 4px; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>LAPORAN PEMESANAN RESTORAN</h1>
                <p>Periode: {$tanggalMulai} s/d {$tanggalSelesai}</p>
            </div>

            <table>
                <thead>
                    <tr>
                        <th style='width: 30px; text-align: center;'>No</th>
                        <th>Pelanggan</th>
                        <th style='text-align: center;'>Meja</th>
                        <th style='text-align: center;'>Waktu Pesan</th>
                        <th style='text-align: center;'>Tamu</th>
                        <th style='text-align: center;'>Status</th>
                        <th style='text-align: right;'>Total</th>
                    </tr>
                </thead>
                <tbody>
                    {$barisTabel}
                </tbody>
            </table>

            <table class='ringkasan'>
                <tr>
                    <td><strong>Total Transaksi:</strong></td>
                    <td style='text-align: right;'>{$ringkasan['total_transaksi']} pesanan</td>
                </tr>
                <tr>
                    <td><strong>Total Pendapatan:</strong></td>
                    <td style='text-align: right;'><strong>{$totalPendapatanFormatted}</strong></td>
                </tr>
            </table>
        </body>
        </html>
        ";

        // Konfigurasi Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        if ($streamLangsung) {
            $dompdf->stream("laporan_pemesanan_{$tanggalMulai}_{$tanggalSelesai}.pdf", ['Attachment' => false]);
            exit;
        }

        return $dompdf->output();
    }
}
