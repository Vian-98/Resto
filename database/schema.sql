-- ========================================================
-- Database Schema: db_restoran
-- Proyek: Sistem Pemesanan Restoran (Sertifikasi BNSP)
-- Unit Kompetensi: J.620100.009.01, J.620100.021.02
-- ========================================================

CREATE DATABASE IF NOT EXISTS db_restoran;
USE db_restoran;

-- Hapus tabel jika sudah ada (sesuai urutan foreign key)
DROP TABLE IF EXISTS detail_pesanan;
DROP TABLE IF EXISTS pemesanan;
DROP TABLE IF EXISTS menu;
DROP TABLE IF EXISTS meja;
DROP TABLE IF EXISTS pelanggan;

-- 1. Tabel Pelanggan
CREATE TABLE pelanggan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    telepon VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Tabel Meja
CREATE TABLE meja (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nomor INT NOT NULL UNIQUE,
    kapasitas INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Tabel Menu
CREATE TABLE menu (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    harga DECIMAL(12, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Tabel Pemesanan (Relasi ke Pelanggan & Meja + Indeks untuk cek bentrok & laporan)
CREATE TABLE pemesanan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pelanggan_id INT NOT NULL,
    meja_id INT NOT NULL,
    waktu_pesan DATETIME NOT NULL,
    jumlah_tamu INT NOT NULL,
    status ENUM('Dipesan', 'Selesai', 'Dibatalkan') DEFAULT 'Dipesan',
    total DECIMAL(12, 2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pemesanan_pelanggan FOREIGN KEY (pelanggan_id) REFERENCES pelanggan(id) ON DELETE CASCADE,
    CONSTRAINT fk_pemesanan_meja FOREIGN KEY (meja_id) REFERENCES meja(id) ON DELETE RESTRICT,
    INDEX idx_meja_waktu (meja_id, waktu_pesan)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Tabel Detail Pesanan (Item menu per pemesanan)
CREATE TABLE detail_pesanan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pemesanan_id INT NOT NULL,
    menu_id INT NOT NULL,
    jumlah INT NOT NULL,
    subtotal DECIMAL(12, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_detail_pemesanan FOREIGN KEY (pemesanan_id) REFERENCES pemesanan(id) ON DELETE CASCADE,
    CONSTRAINT fk_detail_menu FOREIGN KEY (menu_id) REFERENCES menu(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================================
-- Data Awal / Seed Data
-- ========================================================

INSERT INTO pelanggan (nama, telepon) VALUES
('Budi Santoso', '081234567890'),
('Siti Rahma', '085678901234'),
('Ahmad Fauzi', '087812345678');

INSERT INTO meja (nomor, kapasitas) VALUES
(1, 2),
(2, 4),
(3, 4),
(4, 6),
(5, 8);

INSERT INTO menu (nama, harga) VALUES
('Nasi Goreng Spesial', 25000.00),
('Mie Goreng Seafood', 28000.00),
('Ayam Bakar Madu', 30000.00),
('Es Teh Manis', 5000.00),
('Jus Alpukat', 15000.00);

-- Data contoh pemesanan
INSERT INTO pemesanan (pelanggan_id, meja_id, waktu_pesan, jumlah_tamu, status, total) VALUES
(1, 2, '2026-10-12 18:00:00', 3, 'Selesai', 80000.00),
(2, 3, '2026-10-12 19:30:00', 4, 'Dipesan', 60000.00);

INSERT INTO detail_pesanan (pemesanan_id, menu_id, jumlah, subtotal) VALUES
(1, 1, 2, 50000.00),
(1, 4, 2, 10000.00),
(1, 5, 2, 20000.00),
(2, 3, 2, 60000.00);
