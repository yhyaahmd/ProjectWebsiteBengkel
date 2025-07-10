-- Membuat database bengkel
CREATE DATABASE bengkel2;
USE bengkel2;

-- Tabel Master: Pelanggan
CREATE TABLE pelanggan (
    id_pelanggan INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    alamat TEXT,
    no_telepon VARCHAR(15),
    email VARCHAR(100),
    tanggal_daftar DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Master: Mekanik
CREATE TABLE mekanik (
    id_mekanik INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    spesialisasi VARCHAR(100),
    no_telepon VARCHAR(15),
    alamat TEXT,
    tanggal_bergabung DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Master: Sparepart
CREATE TABLE sparepart (
    id_sparepart INT AUTO_INCREMENT PRIMARY KEY,
    nama_sparepart VARCHAR(100) NOT NULL,
    harga DECIMAL(10, 2) NOT NULL,
    stok INT NOT NULL DEFAULT 0,
    kategori VARCHAR(50),
    deskripsi TEXT
);

-- Tabel Transaksi: Servis
CREATE TABLE servis (
    id_servis INT AUTO_INCREMENT PRIMARY KEY,
    id_pelanggan INT,
    id_mekanik INT,
    tanggal_servis DATETIME DEFAULT CURRENT_TIMESTAMP,
    jenis_kendaraan VARCHAR(50),
    no_plat VARCHAR(20),
    keluhan TEXT,
    STATUS ENUM('Menunggu', 'Proses', 'Selesai', 'Diambil') DEFAULT 'Menunggu',
    total_biaya DECIMAL(10, 2) DEFAULT 0,
    FOREIGN KEY (id_pelanggan) REFERENCES pelanggan(id_pelanggan),
    FOREIGN KEY (id_mekanik) REFERENCES mekanik(id_mekanik)
);

-- Tabel Transaksi: Detail Servis
CREATE TABLE detail_servis (
    id_detail INT AUTO_INCREMENT PRIMARY KEY,
    id_servis INT,
    id_sparepart INT NULL,
    nama_jasa VARCHAR(100) NULL,
    jumlah INT DEFAULT 1,
    harga_satuan DECIMAL(10, 2),
    subtotal DECIMAL(10, 2),
    FOREIGN KEY (id_servis) REFERENCES servis(id_servis),
    FOREIGN KEY (id_sparepart) REFERENCES sparepart(id_sparepart)
);

-- Tabel User untuk login
CREATE TABLE users (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    PASSWORD VARCHAR(255) NOT NULL,
    nama VARCHAR(100) NOT NULL,
    ROLE ENUM('admin', 'kasir', 'user') NOT NULL,
    last_login DATETIME
);

-- View 1: Daftar Servis Aktif
CREATE VIEW v_servis_aktif AS
SELECT s.id_servis, p.nama AS nama_pelanggan, s.no_plat, s.jenis_kendaraan, 
       s.keluhan, s.tanggal_servis, s.status, m.nama AS nama_mekanik
FROM servis s
JOIN pelanggan p ON s.id_pelanggan = p.id_pelanggan
LEFT JOIN mekanik m ON s.id_mekanik = m.id_mekanik
WHERE s.status IN ('Menunggu', 'Proses');

-- View 2: Laporan Pendapatan Harian
CREATE VIEW v_pendapatan_harian AS
SELECT DATE(tanggal_servis) AS tanggal, COUNT(*) AS jumlah_servis, 
       SUM(total_biaya) AS total_pendapatan
FROM servis
WHERE STATUS = 'Selesai' OR STATUS = 'Diambil'
GROUP BY DATE(tanggal_servis);

-- View 3: Detail Transaksi Lengkap
CREATE VIEW v_detail_transaksi AS
SELECT s.id_servis, s.tanggal_servis, p.nama AS nama_pelanggan, p.no_telepon,
       s.no_plat, s.jenis_kendaraan, m.nama AS nama_mekanik,
       ds.nama_jasa, sp.nama_sparepart, ds.jumlah, ds.harga_satuan, ds.subtotal,
       s.total_biaya, s.status
FROM servis s
JOIN pelanggan p ON s.id_pelanggan = p.id_pelanggan
LEFT JOIN mekanik m ON s.id_mekanik = m.id_mekanik
LEFT JOIN detail_servis ds ON s.id_servis = ds.id_servis
LEFT JOIN sparepart sp ON ds.id_sparepart = sp.id_sparepart;

-- View 4: Stok Sparepart Menipis
CREATE VIEW v_stok_menipis AS
SELECT id_sparepart, nama_sparepart, stok, harga, kategori
FROM sparepart
WHERE stok <= 5
ORDER BY stok ASC;

-- View 5: Kinerja Mekanik
CREATE VIEW v_kinerja_mekanik AS
SELECT m.id_mekanik, m.nama, m.spesialisasi,
       COUNT(s.id_servis) AS jumlah_servis,
       SUM(s.total_biaya) AS total_pendapatan
FROM mekanik m
LEFT JOIN servis s ON m.id_mekanik = s.id_mekanik
WHERE s.status = 'Selesai' OR s.status = 'Diambil'
GROUP BY m.id_mekanik, m.nama, m.spesialisasi;




-- STORED
-- Stored Procedure 1: Tambah Servis Baru
DELIMITER //
CREATE PROCEDURE sp_tambah_servis(
    IN p_id_pelanggan INT,
    IN p_id_mekanik INT,
    IN p_jenis_kendaraan VARCHAR(50),
    IN p_no_plat VARCHAR(20),
    IN p_keluhan TEXT,
    OUT p_id_servis INT
)
BEGIN
    INSERT INTO servis (id_pelanggan, id_mekanik, jenis_kendaraan, no_plat, keluhan)
    VALUES (p_id_pelanggan, p_id_mekanik, p_jenis_kendaraan, p_no_plat, p_keluhan);
    
    SET p_id_servis = LAST_INSERT_ID();
END //
DELIMITER ;

-- Stored Procedure 2: Tambah Detail Servis
DELIMITER //
CREATE PROCEDURE sp_tambah_detail_servis(
    IN p_id_servis INT,
    IN p_id_sparepart INT,
    IN p_nama_jasa VARCHAR(100),
    IN p_jumlah INT,
    IN p_harga_satuan DECIMAL(10, 2)
)
BEGIN
    DECLARE v_subtotal DECIMAL(10, 2);
    DECLARE v_stok_saat_ini INT;
    
    -- Jika ada sparepart, periksa stok
    IF p_id_sparepart IS NOT NULL THEN
        SELECT stok INTO v_stok_saat_ini FROM sparepart WHERE id_sparepart = p_id_sparepart;
        
        IF v_stok_saat_ini >= p_jumlah THEN
            -- Kurangi stok
            UPDATE sparepart SET stok = stok - p_jumlah WHERE id_sparepart = p_id_sparepart;
        ELSE
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Stok sparepart tidak mencukupi';
        END IF;
    END IF;
    
    -- Hitung subtotal
    SET v_subtotal = p_jumlah * p_harga_satuan;
    
    -- Tambahkan detail servis
    INSERT INTO detail_servis (id_servis, id_sparepart, nama_jasa, jumlah, harga_satuan, subtotal)
    VALUES (p_id_servis, p_id_sparepart, p_nama_jasa, p_jumlah, p_harga_satuan, v_subtotal);
    
    -- Update total biaya di tabel servis
    UPDATE servis SET total_biaya = total_biaya + v_subtotal WHERE id_servis = p_id_servis;
END //
DELIMITER ;

-- Stored Procedure 3: Update Status Servis
DELIMITER //
CREATE PROCEDURE sp_update_status_servis(
    IN p_id_servis INT,
    IN p_status ENUM('Menunggu', 'Proses', 'Selesai', 'Diambil')
)
BEGIN
    UPDATE servis SET STATUS = p_status WHERE id_servis = p_id_servis;
END //
DELIMITER ;

-- Stored Procedure 4: Cari Servis (dengan Looping)
DELIMITER //
CREATE PROCEDURE sp_cari_servis(
    IN p_keyword VARCHAR(100)
)
BEGIN
    -- Semua deklarasi variabel harus di awal
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_id_servis INT;
    DECLARE v_nama_pelanggan VARCHAR(100);
    DECLARE v_no_plat VARCHAR(20);
    DECLARE v_status VARCHAR(20);
    
    -- Deklarasi cursor harus setelah semua deklarasi variabel
    DECLARE cur CURSOR FOR 
        SELECT s.id_servis, p.nama, s.no_plat, s.status
        FROM servis s
        JOIN pelanggan p ON s.id_pelanggan = p.id_pelanggan
        WHERE p.nama LIKE CONCAT('%', p_keyword, '%')
        OR s.no_plat LIKE CONCAT('%', p_keyword, '%')
        OR s.keluhan LIKE CONCAT('%', p_keyword, '%');
    
    -- Deklarasi handler harus setelah deklarasi cursor
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    -- Setelah semua deklarasi, baru pernyataan SQL lainnya
    DROP TEMPORARY TABLE IF EXISTS temp_hasil_pencarian;
    CREATE TEMPORARY TABLE temp_hasil_pencarian (
        id_servis INT,
        nama_pelanggan VARCHAR(100),
        no_plat VARCHAR(20),
        STATUS VARCHAR(20)
    );
    
    OPEN cur;
    
    read_loop: LOOP
        FETCH cur INTO v_id_servis, v_nama_pelanggan, v_no_plat, v_status;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- Masukkan ke temporary table
        INSERT INTO temp_hasil_pencarian VALUES (v_id_servis, v_nama_pelanggan, v_no_plat, v_status);
    END LOOP;
    
    CLOSE cur;
    
    -- Tampilkan hasil
    SELECT * FROM temp_hasil_pencarian;
END //
DELIMITER ;

-- Stored Procedure 5: Laporan Pendapatan Periode
DELIMITER //
CREATE PROCEDURE sp_laporan_pendapatan(
    IN p_tanggal_mulai DATE,
    IN p_tanggal_akhir DATE
)
BEGIN
    SELECT 
        DATE(tanggal_servis) AS tanggal,
        COUNT(*) AS jumlah_servis,
        SUM(total_biaya) AS total_pendapatan
    FROM servis
    WHERE DATE(tanggal_servis) BETWEEN p_tanggal_mulai AND p_tanggal_akhir
    AND (STATUS = 'Selesai' OR STATUS = 'Diambil')
    GROUP BY DATE(tanggal_servis)
    ORDER BY tanggal;
END //
DELIMITER ;



-- TRIGGERS
-- Trigger 1: Log perubahan stok sparepart (INSERT)
DELIMITER //
CREATE TRIGGER trg_log_stok_insert
AFTER INSERT ON sparepart
FOR EACH ROW
BEGIN
    INSERT INTO log_stok (id_sparepart, nama_sparepart, stok_lama, stok_baru, keterangan, waktu)
    VALUES (NEW.id_sparepart, NEW.nama_sparepart, 0, NEW.stok, 'Sparepart baru ditambahkan', NOW());
END //
DELIMITER ;

-- Buat tabel log_stok terlebih dahulu
CREATE TABLE log_stok (
    id_log INT AUTO_INCREMENT PRIMARY KEY,
    id_sparepart INT,
    nama_sparepart VARCHAR(100),
    stok_lama INT,
    stok_baru INT,
    keterangan VARCHAR(255),
    waktu DATETIME,
    FOREIGN KEY (id_sparepart) REFERENCES sparepart(id_sparepart)
);

-- Trigger 2: Log perubahan stok sparepart (UPDATE)
DELIMITER //
CREATE TRIGGER trg_log_stok_update
AFTER UPDATE ON sparepart
FOR EACH ROW
BEGIN
    IF OLD.stok != NEW.stok THEN
        INSERT INTO log_stok (id_sparepart, nama_sparepart, stok_lama, stok_baru, keterangan, waktu)
        VALUES (NEW.id_sparepart, NEW.nama_sparepart, OLD.stok, NEW.stok, 'Stok diperbarui', NOW());
    END IF;
END //
DELIMITER ;

-- Trigger 3: Hitung total biaya servis (INSERT pada detail_servis)
DELIMITER //
CREATE TRIGGER trg_hitung_total_insert
AFTER INSERT ON detail_servis
FOR EACH ROW
BEGIN
    UPDATE servis 
    SET total_biaya = (
        SELECT SUM(subtotal) 
        FROM detail_servis 
        WHERE id_servis = NEW.id_servis
    )
    WHERE id_servis = NEW.id_servis;
END //
DELIMITER ;

-- Trigger 4: Hitung total biaya servis (DELETE pada detail_servis)
DELIMITER //
CREATE TRIGGER trg_hitung_total_delete
AFTER DELETE ON detail_servis
FOR EACH ROW
BEGIN
    UPDATE servis 
    SET total_biaya = (
        SELECT COALESCE(SUM(subtotal), 0) 
        FROM detail_servis 
        WHERE id_servis = OLD.id_servis
    )
    WHERE id_servis = OLD.id_servis;
END //
DELIMITER ;

-- Trigger 5: Kembalikan stok saat detail servis dihapus
DELIMITER //
CREATE TRIGGER trg_kembalikan_stok
BEFORE DELETE ON detail_servis
FOR EACH ROW
BEGIN
    IF OLD.id_sparepart IS NOT NULL THEN
        UPDATE sparepart 
        SET stok = stok + OLD.jumlah 
        WHERE id_sparepart = OLD.id_sparepart;
    END IF;
END //
DELIMITER ;


-- Tambah data mekanik
INSERT INTO mekanik (nama, spesialisasi, no_telepon, alamat) VALUES
('Budi Santoso', 'Mesin', '081234567890', 'Jl. Mekanik No. 1'),
('Ahmad Hidayat', 'Kelistrikan', '082345678901', 'Jl. Teknisi No. 2'),
('Dedi Cahyono', 'Body Repair', '083456789012', 'Jl. Bengkel No. 3');

-- Tambah data sparepart
INSERT INTO sparepart (nama_sparepart, harga, stok, kategori, deskripsi) VALUES
('Oli Mesin', 50000, 20, 'Pelumas', 'Oli mesin kualitas tinggi'),
('Filter Oli', 35000, 15, 'Filter', 'Filter oli standar'),
('Busi', 25000, 30, 'Kelistrikan', 'Busi standar'),
('Kampas Rem', 150000, 10, 'Rem', 'Kampas rem depan'),
('Aki', 350000, 5, 'Kelistrikan', 'Aki 12V');

-- Tambah data pelanggan
INSERT INTO pelanggan (nama, alamat, no_telepon, email) VALUES
('Joko Widodo', 'Jl. Pelanggan No. 1', '087654321098', 'joko@email.com'),
('Susi Susanti', 'Jl. Customer No. 2', '086543210987', 'susi@email.com'),
('Bambang Pamungkas', 'Jl. Klien No. 3', '085432109876', 'bambang@email.com');

-- Tambah data user
INSERT INTO users (username, PASSWORD, nama, ROLE) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin'),
('kasir', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Kasir Bengkel', 'kasir'),
('user', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User Biasa', 'user');
-- Password untuk semua user adalah 'password'