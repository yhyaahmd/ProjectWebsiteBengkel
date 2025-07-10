<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

requireRole('user');

$pageTitle = "Riwayat Servis";
include '../../includes/header.php';

// Ambil id_pelanggan dari user yang login
$id_pelanggan = 0;
$userQuery = "SELECT id_pelanggan FROM users WHERE id_user = ?";
$userStmt = mysqli_prepare($conn, $userQuery);
mysqli_stmt_bind_param($userStmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($userStmt);
$userResult = mysqli_stmt_get_result($userStmt);
if ($userData = mysqli_fetch_assoc($userResult)) {
    $id_pelanggan = $userData['id_pelanggan'];
}

// Jika ada parameter id, tampilkan detail servis
if (isset($_GET['id'])) {
    $id_servis = $_GET['id'];
    
    // Verifikasi bahwa servis ini milik pelanggan yang login
    $verifyQuery = "SELECT COUNT(*) as count FROM servis WHERE id_servis = ? AND id_pelanggan = ?";
    $verifyStmt = mysqli_prepare($conn, $verifyQuery);
    mysqli_stmt_bind_param($verifyStmt, "ii", $id_servis, $id_pelanggan);
    mysqli_stmt_execute($verifyStmt);
    $verifyResult = mysqli_stmt_get_result($verifyStmt);
    $verifyData = mysqli_fetch_assoc($verifyResult);
    
    if ($verifyData['count'] == 0) {
        echo "<div class='error-message'>Anda tidak memiliki akses ke servis ini</div>";
        echo "<a href='riwayat.php' class='btn btn-secondary'>Kembali</a>";
    } else {
        // Ambil data servis
        $servisQuery = "SELECT s.*, p.nama as nama_pelanggan, p.no_telepon, m.nama as nama_mekanik 
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
        ?>

<div class="content-container">
    <h2>Detail Servis #<?php echo $id_servis; ?></h2>

    <div class="detail-container">
        <div class="detail-section">
            <h3>Informasi Servis</h3>
            <table class="detail-table">
                <tr>
                    <th>Tanggal Servis:</th>
                    <td><?php echo date('d/m/Y H:i', strtotime($servis['tanggal_servis'])); ?></td>
                </tr>
                <tr>
                    <th>Status:</th>
                    <td><?php echo $servis['status']; ?></td>
                </tr>
                <tr>
                    <th>Jenis Kendaraan:</th>
                    <td><?php echo htmlspecialchars($servis['jenis_kendaraan']); ?></td>
                </tr>
                <tr>
                    <th>No. Plat:</th>
                    <td><?php echo htmlspecialchars($servis['no_plat']); ?></td>
                </tr>
                <tr>
                    <th>Keluhan:</th>
                    <td><?php echo htmlspecialchars($servis['keluhan']); ?></td>
                </tr>
                <tr>
                    <th>Mekanik:</th>
                    <td><?php echo htmlspecialchars($servis['nama_mekanik'] ?? 'Belum ditugaskan'); ?></td>
                </tr>
                <tr>
                    <th>Total Biaya:</th>
                    <td>Rp <?php echo number_format($servis['total_biaya'], 0, ',', '.'); ?></td>
                </tr>
            </table>
        </div>

        <div class="detail-section">
            <h3>Detail Item dan Jasa</h3>
            <table>
                <thead>
                    <tr>
                        <th>Item/Jasa</th>
                        <th>Jumlah</th>
                        <th>Harga Satuan</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                            if (mysqli_num_rows($detailResult) > 0) {
                                while ($detail = mysqli_fetch_assoc($detailResult)) {
                                    echo "<tr>";
                                    // Tampilkan nama sparepart jika ada, jika tidak tampilkan nama jasa
                                    if (!empty($detail['nama_sparepart'])) {
                                        echo "<td>" . htmlspecialchars($detail['nama_sparepart']) . " (Sparepart)</td>";
                                    } else {
                                        echo "<td>" . htmlspecialchars($detail['nama_jasa']) . " (Jasa)</td>";
                                    }
                                    echo "<td>" . $detail['jumlah'] . "</td>";
                                    echo "<td>Rp " . number_format($detail['harga_satuan'], 0, ',', '.') . "</td>";
                                    echo "<td>Rp " . number_format($detail['subtotal'], 0, ',', '.') . "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='4'>Belum ada item atau jasa</td></tr>";
                            }
                            ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3">Total</th>
                        <th>Rp <?php echo number_format($servis['total_biaya'], 0, ',', '.'); ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="action-buttons">
        <a href="riwayat.php" class="btn btn-secondary">Kembali ke Riwayat Servis</a>
        <?php if ($servis['status'] == 'Selesai' || $servis['status'] == 'Diambil'): ?>
        <button onclick="window.print()" class="btn btn-primary">Cetak Detail</button>
        <?php endif; ?>
    </div>
</div>

<?php
    }
} else {
    // Tampilkan daftar riwayat servis
    $query = "SELECT * FROM servis WHERE id_pelanggan = ? ORDER BY tanggal_servis DESC";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $id_pelanggan);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    ?>

<div class="content-container">
    <h2>Riwayat Servis</h2>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tanggal</th>
                    <th>Kendaraan</th>
                    <th>No. Plat</th>
                    <th>Status</th>
                    <th>Total Biaya</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    if (mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            echo "<tr>";
                            echo "<td>" . $row['id_servis'] . "</td>";
                            echo "<td>" . date('d/m/Y H:i', strtotime($row['tanggal_servis'])) . "</td>";
                            echo "<td>" . htmlspecialchars($row['jenis_kendaraan']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['no_plat']) . "</td>";
                            echo "<td>" . $row['status'] . "</td>";
                            echo "<td>Rp " . number_format($row['total_biaya'], 0, ',', '.') . "</td>";
                            echo "<td><a href='?id=" . $row['id_servis'] . "' class='btn btn-view'>Detail</a></td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7'>Belum ada riwayat servis</td></tr>";
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