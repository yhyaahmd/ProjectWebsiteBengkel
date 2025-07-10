<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

requireRole('kasir');

$pageTitle = "Transaksi";
include '../../includes/header.php';

// Proses cetak nota
if (isset($_GET['print'])) {
    $id_servis = $_GET['print'];
    
    // Ambil data servis
    $servisQuery = "SELECT s.*, p.nama as nama_pelanggan, p.no_telepon, p.alamat, m.nama as nama_mekanik 
                    FROM servis s
                    JOIN pelanggan p ON s.id_pelanggan = p.id_pelanggan
                    LEFT JOIN mekanik m ON s.id_mekanik = m.id_mekanik
                    WHERE s.id_servis = ?";
    $servisStmt = mysqli_prepare($conn, $servisQuery);
    mysqli_stmt_bind_param($servisStmt, "i", $id_servis);
    mysqli_stmt_execute($servisStmt);
    $servisResult = mysqli_stmt_get_result($servisStmt);
    $servis = mysqli_fetch_assoc($servisResult);
    
    // Ambil detail servis
    $detailQuery = "SELECT d.*, s.nama_sparepart 
                    FROM detail_servis d
                    LEFT JOIN sparepart s ON d.id_sparepart = s.id_sparepart
                    WHERE d.id_servis = ?";
    $detailStmt = mysqli_prepare($conn, $detailQuery);
    mysqli_stmt_bind_param($detailStmt, "i", $id_servis);
    mysqli_stmt_execute($detailStmt);
    $detailResult = mysqli_stmt_get_result($detailStmt);
    
    // Tampilkan nota
    if ($servis) {
        ?>
<div class="content-container" id="printArea">
    <div style="text-align: center; margin-bottom: 1.5rem;">
        <h2>NOTA SERVIS</h2>
        <h3>Bengkel System</h3>
        <p>Jl. Contoh No. 123, Kota Contoh</p>
        <p>Telp: 0812-3456-7890</p>
    </div>

    <div style="border-top: 1px solid #ddd; border-bottom: 1px solid #ddd; padding: 1rem 0; margin-bottom: 1.5rem;">
        <table style="width: 100%;">
            <tr>
                <td style="width: 50%;">
                    <p><strong>No. Servis:</strong> #<?php echo $id_servis; ?></p>
                    <p><strong>Tanggal:</strong> <?php echo date('d/m/Y H:i', strtotime($servis['tanggal_servis'])); ?>
                    </p>
                    <p><strong>Status:</strong> <?php echo $servis['status']; ?></p>
                </td>
                <td style="width: 50%;">
                    <p><strong>Pelanggan:</strong> <?php echo htmlspecialchars($servis['nama_pelanggan']); ?></p>
                    <p><strong>No. Telepon:</strong> <?php echo htmlspecialchars($servis['no_telepon']); ?></p>
                    <p><strong>Alamat:</strong> <?php echo htmlspecialchars($servis['alamat']); ?></p>
                </td>
            </tr>
        </table>
    </div>

    <div style="margin-bottom: 1.5rem;">
        <p><strong>Kendaraan:</strong> <?php echo htmlspecialchars($servis['jenis_kendaraan']); ?></p>
        <p><strong>No. Plat:</strong> <?php echo htmlspecialchars($servis['no_plat']); ?></p>
        <p><strong>Keluhan:</strong> <?php echo htmlspecialchars($servis['keluhan']); ?></p>
        <p><strong>Mekanik:</strong> <?php echo htmlspecialchars($servis['nama_mekanik'] ?? 'Belum ditugaskan'); ?></p>
    </div>

    <table style="width: 100%; border-collapse: collapse; margin-bottom: 1.5rem;">
        <thead>
            <tr>
                <th style="border: 1px solid #ddd; padding: 0.5rem; text-align: left;">Item/Jasa</th>
                <th style="border: 1px solid #ddd; padding: 0.5rem; text-align: right;">Jumlah</th>
                <th style="border: 1px solid #ddd; padding: 0.5rem; text-align: right;">Harga Satuan</th>
                <th style="border: 1px solid #ddd; padding: 0.5rem; text-align: right;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php
                    $total = 0;
                    while ($detail = mysqli_fetch_assoc($detailResult)) {
                        echo "<tr>";
                        // Tampilkan nama sparepart jika ada, jika tidak tampilkan nama jasa
                        if (!empty($detail['nama_sparepart'])) {
                            echo "<td style='border: 1px solid #ddd; padding: 0.5rem;'>" . htmlspecialchars($detail['nama_sparepart']) . " (Sparepart)</td>";
                        } else {
                            echo "<td style='border: 1px solid #ddd; padding: 0.5rem;'>" . htmlspecialchars($detail['nama_jasa']) . " (Jasa)</td>";
                        }
                        echo "<td style='border: 1px solid #ddd; padding: 0.5rem; text-align: right;'>" . $detail['jumlah'] . "</td>";
                        echo "<td style='border: 1px solid #ddd; padding: 0.5rem; text-align: right;'>Rp " . number_format($detail['harga_satuan'], 0, ',', '.') . "</td>";
                        echo "<td style='border: 1px solid #ddd; padding: 0.5rem; text-align: right;'>Rp " . number_format($detail['subtotal'], 0, ',', '.') . "</td>";
                        echo "</tr>";
                        $total += $detail['subtotal'];
                    }
                    ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3" style="border: 1px solid #ddd; padding: 0.5rem; text-align: right;">Total</th>
                <th style="border: 1px solid #ddd; padding: 0.5rem; text-align: right;">Rp
                    <?php echo number_format($servis['total_biaya'], 0, ',', '.'); ?></th>
            </tr>
        </tfoot>
    </table>

    <div style="margin-top: 2rem; text-align: center;">
        <p>Terima kasih atas kepercayaan Anda menggunakan jasa bengkel kami.</p>
        <p>Garansi servis berlaku 7 hari sejak tanggal servis.</p>
    </div>
</div>

<div class="action-buttons" style="margin-top: 1.5rem; text-align: center;">
    <button onclick="window.print()" class="btn btn-primary">Cetak Nota</button>
    <a href="servis.php" class="btn btn-secondary">Kembali ke Daftar Servis</a>
</div>
<?php
    } else {
        echo "<div class='error-message'>Servis tidak ditemukan</div>";
        echo "<a href='servis.php' class='btn btn-secondary'>Kembali ke Daftar Servis</a>";
    }
} else {
    // Tampilkan daftar transaksi
    
    // Filter tanggal
    $tanggal_mulai = isset($_GET['tanggal_mulai']) ? $_GET['tanggal_mulai'] : date('Y-m-d');
    $tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-d');
    
    // Hitung total pendapatan
    $totalQuery = "SELECT SUM(total_biaya) as total FROM servis 
                  WHERE DATE(tanggal_servis) BETWEEN ? AND ? AND status = 'Diambil'";
    $totalStmt = mysqli_prepare($conn, $totalQuery);
    mysqli_stmt_bind_param($totalStmt, "ss", $tanggal_mulai, $tanggal_akhir);
    mysqli_stmt_execute($totalStmt);
    $totalResult = mysqli_stmt_get_result($totalStmt);
    $totalData = mysqli_fetch_assoc($totalResult);
    $totalPendapatan = $totalData['total'] ?? 0;
    ?>

<div class="content-container">
    <h2>Transaksi</h2>

    <!-- Form Filter -->
    <div class="form-container">
        <form method="get" action="">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <div class="form-group">
                    <label for="tanggal_mulai">Tanggal Mulai:</label>
                    <input type="date" id="tanggal_mulai" name="tanggal_mulai" value="<?php echo $tanggal_mulai; ?>">
                </div>

                <div class="form-group">
                    <label for="tanggal_akhir">Tanggal Akhir:</label>
                    <input type="date" id="tanggal_akhir" name="tanggal_akhir" value="<?php echo $tanggal_akhir; ?>">
                </div>

                <div class="form-group" style="display: flex; align-items: flex-end;">
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>
            </div>
        </form>
    </div>

    <div class="detail-section" style="text-align: center; padding: 1.5rem; margin-bottom: 1.5rem;">
        <h3>Total Pendapatan</h3>
        <p style="font-size: 2rem; font-weight: bold;">Rp <?php echo number_format($totalPendapatan, 0, ',', '.'); ?>
        </p>
    </div>

    <!-- Tabel Transaksi -->
    <div class="table-container">
        <h3>Daftar Transaksi (<?php echo date('d/m/Y', strtotime($tanggal_mulai)); ?> -
            <?php echo date('d/m/Y', strtotime($tanggal_akhir)); ?>)</h3>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tanggal</th>
                    <th>Pelanggan</th>
                    <th>Kendaraan</th>
                    <th>Status</th>
                    <th>Total Biaya</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    $query = "SELECT s.*, p.nama as nama_pelanggan
                              FROM servis s
                              JOIN pelanggan p ON s.id_pelanggan = p.id_pelanggan
                              WHERE DATE(s.tanggal_servis) BETWEEN ? AND ?
                              ORDER BY s.tanggal_servis DESC";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, "ss", $tanggal_mulai, $tanggal_akhir);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    
                    if (mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            echo "<tr>";
                            echo "<td>" . $row['id_servis'] . "</td>";
                            echo "<td>" . date('d/m/Y H:i', strtotime($row['tanggal_servis'])) . "</td>";
                            echo "<td>" . htmlspecialchars($row['nama_pelanggan']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['jenis_kendaraan']) . " (" . htmlspecialchars($row['no_plat']) . ")</td>";
                            echo "<td>" . $row['status'] . "</td>";
                            echo "<td>Rp " . number_format($row['total_biaya'], 0, ',', '.') . "</td>";
                            echo "<td>
                                    <a href='servis.php?id=" . $row['id_servis'] . "' class='btn btn-view'>Detail</a>
                                    <a href='?print=" . $row['id_servis'] . "' class='btn btn-primary'>Cetak</a>
                                  </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7'>Tidak ada transaksi pada periode tersebut</td></tr>";
                    }
                    ?>
            </tbody>
        </table>
    </div>
</div>
<?php
}

include '../../includes/footer.php';
?>