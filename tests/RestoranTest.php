<?php
/**
 * Test Suite Sistem Pemesanan Restoran
 * Unit Kompetensi: J.620100.033.02 (Melaksanakan Pengujian Unit Program)
 *
 * Menguji skenario normal, skenario edge case, dan penanganan error
 * sesuai PRD Bagian 8 (T-01 s/d T-07).
 */

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Pemesanan;
use App\Database;

class RestoranTest extends TestCase
{
    private Pemesanan $pemesanan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pemesanan = new Pemesanan();
    }

    /**
     * T-01: Pengujian hitungTotal() skenario normal (2 item menu)
     */
    public function testHitungTotalNormalDuaItem(): void
    {
        // Item 1: Nasi Goreng (2 porsi x Rp 25.000 = Rp 50.000)
        $this->pemesanan->tambahItem(1, 2, 25000.0);
        // Item 2: Es Teh (3 gelas x Rp 5.000 = Rp 15.000)
        $this->pemesanan->tambahItem(4, 3, 5000.0);

        $total = $this->pemesanan->hitungTotal();

        $this->assertEquals(65000.0, $total, 'Total belanja 2 item harus sesuai akumulasi harga x jumlah');
    }

    /**
     * T-02: Pengujian hitungTotal() skenario edge case (tanpa item / pesanan kosong)
     */
    public function testHitungTotalEdgeCaseTanpaItem(): void
    {
        $total = $this->pemesanan->hitungTotal();

        $this->assertEquals(0.0, $total, 'Total belanja tanpa item harus bernilai 0.0');
    }

    /**
     * T-03: Pengujian cekBentrok() saat meja dan waktu pemesanan SAMA (Bentrok / Ditolak)
     */
    public function testCekBentrokMejaDanWaktuSamaDitolak(): void
    {
        // Meja 2 sudah dipesan pada '2026-10-12 18:00:00' (dari seed data)
        $bentrok = $this->pemesanan->cekBentrok(2, '2026-10-12 18:00:00');

        $this->assertTrue($bentrok, 'Pemesanan harus terdeteksi bentrok (true) jika meja dan jam sama');
    }

    /**
     * T-04: Pengujian cekBentrok() saat meja sama namun waktu BERBEDA (Diterima)
     */
    public function testCekBentrokMejaSamaWaktuBerbedaDiterima(): void
    {
        // Meja 2 dipesan pada waktu lain yang kosong
        $bentrok = $this->pemesanan->cekBentrok(2, '2026-10-15 11:00:00');

        $this->assertFalse($bentrok, 'Pemesanan tidak boleh bentrok (false) jika waktu berbeda');
    }

    /**
     * T-05: Pengujian validasiKapasitas() saat jumlah tamu melebihi kapasitas meja (Ditolak)
     */
    public function testValidasiKapasitasTamuMelebihiKapasitasDitolak(): void
    {
        // Meja 1 hanya berkapasitas 2 orang
        $valid = $this->pemesanan->validasiKapasitas(1, 5);
        $this->assertFalse($valid, 'Jumlah tamu 5 pada meja kapasitas 2 harus ditolak (false)');

        // Pengujian kasus valid (sesuai kapasitas)
        $validNormal = $this->pemesanan->validasiKapasitas(1, 2);
        $this->assertTrue($validNormal, 'Jumlah tamu 2 pada meja kapasitas 2 harus diterima (true)');
    }

    /**
     * T-06: Pengujian helper format_rupiah() dengan nominal angka
     */
    public function testFormatRupiah(): void
    {
        $hasil = format_rupiah(15000);
        $this->assertEquals('Rp 15.000', $hasil);

        $hasilNol = format_rupiah(0);
        $this->assertEquals('Rp 0', $hasilNol);
    }

    /**
     * T-07: Pengujian helper validasi_input() dengan input kosong (Ditolak)
     */
    public function testValidasiInputKosongDitolak(): void
    {
        $this->assertFalse(validasi_input(''), 'Input string kosong harus menghasilkan false');
        $this->assertFalse(validasi_input('   '), 'Input hanya spasi harus menghasilkan false');
        $this->assertFalse(validasi_input(null), 'Input null harus menghasilkan false');

        // Pengujian input valid
        $this->assertEquals('Budi Santoso', validasi_input('  Budi Santoso  '));
    }

    /**
     * T-08: Pengujian penggabungan kuantitas saat menu yang sama ditambahkan berulang
     */
    public function testTambahItemGabungKuantitas(): void
    {
        // Tambah Jus Alpukat pertama kali: 1 porsi @ 15.000
        $this->pemesanan->tambahItem(5, 1, 15000.0);
        // Tambah Jus Alpukat kedua kali: 2 porsi @ 15.000
        $this->pemesanan->tambahItem(5, 2, 15000.0);

        $items = $this->pemesanan->getItems();
        $this->assertCount(1, $items, 'Menu yang sama tidak boleh membuat baris terpisah, harus digabung');
        $this->assertEquals(3, $items[0]['jumlah'], 'Kuantitas menu yang sama harus dijumlahkan (1 + 2 = 3)');
        $this->assertEquals(45000.0, $this->pemesanan->hitungTotal(), 'Total harus sesuai kuantitas akumulasi');
    }
}
