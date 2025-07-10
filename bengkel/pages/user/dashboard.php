<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

requireRole('user');

$pageTitle = "Dashboard Pelanggan";
include '../../includes/header.php';

// Ambil data user yang sedang login
$userQuery = "SELECT * FROM users WHERE id_user = ?";
$userStmt = mysqli_prepare($conn, $userQuery);
mysqli_stmt_bind_param($userStmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($userStmt);
$userResult = mysqli_stmt_get_result($userStmt);
$userData = mysqli_fetch_assoc($userResult);

// Cari data pelanggan berdasarkan nama user (atau bisa berdasarkan email)
$id_pelanggan = 0;
$pelanggan = null;

if ($userData) {
    $pelangganQuery = "SELECT * FROM pelanggan WHERE nama = ? OR email = ?";
    $pelangganStmt = mysqli_prepare($conn, $pelangganQuery);
    mysqli_stmt_bind_param($pelangganStmt, "ss", $userData['nama'], $userData['nama']);
    mysqli_stmt_execute($pelangganStmt);
    $pelangganResult = mysqli_stmt_get_result($pelangganStmt);
    
    if ($pelangganData = mysqli_fetch_assoc($pelangganResult)) {
        $id_pelanggan = $pelangganData['id_pelanggan'];
        $pelanggan = $pelangganData;
    }
}

// Jika pelanggan tidak ditemukan, tampilkan pesan
if (!$pelanggan) {
    echo "<div class='error-message'>Data pelanggan tidak ditemukan. Silakan hubungi admin untuk menghubungkan akun Anda dengan data pelanggan.</div>";
    include '../../includes/footer.php';
    exit;
}

// Hitung jumlah servis
$totalServisQuery = "SELECT COUNT(*) as total FROM servis WHERE id_pelanggan = ?";
$totalServisStmt = mysqli_prepare($conn, $totalServisQuery);
mysqli_stmt_bind_param($totalServisStmt, "i", $id_pelanggan);
mysqli_stmt_execute($totalServisStmt);
$totalServisResult = mysqli_stmt_get_result($totalServisStmt);
$totalServis = mysqli_fetch_assoc($totalServisResult)['total'];

// Ambil servis aktif
$servisAktifQuery = "SELECT * FROM servis WHERE id_pelanggan = ? AND status != 'Diambil' ORDER BY tanggal_servis DESC";
$servisAktifStmt = mysqli_prepare($conn, $servisAktifQuery);
mysqli_stmt_bind_param($servisAktifStmt, "i", $id_pelanggan);
mysqli_stmt_execute($servisAktifStmt);
$servisAktifResult = mysqli_stmt_get_result($servisAktifStmt);

// Ambil riwayat servis terakhir
$riwayatServisQuery = "SELECT * FROM servis WHERE id_pelanggan = ? AND status = 'Diambil' ORDER BY tanggal_servis DESC LIMIT 5";
$riwayatServisStmt = mysqli_prepare($conn, $riwayatServisQuery);
mysqli_stmt_bind_param($riwayatServisStmt, "i", $id_pelanggan);
mysqli_stmt_execute($riwayatServisStmt);
$riwayatServisResult = mysqli_stmt_get_result($riwayatServisStmt);
?>

<div class="content-container">
    <h2>Dashboard Pelanggan</h2>

    <div class="detail-section" style="margin-bottom: 2rem;">
        <h3>Informasi Pelanggan</h3>
        <table class="detail-table">
            <tr>
                <th>Nama:</th>
                <td><?php echo htmlspecialchars($pelanggan['nama'] ?? '-'); ?></td>
            </tr>
            <tr>
                <th>No. Telepon:</th>
                <td><?php echo htmlspecialchars($pelanggan['no_telepon'] ?? '-'); ?></td>
            </tr>
            <tr>
                <th>Alamat:</th>
                <td><?php echo htmlspecialchars($pelanggan['alamat'] ?? '-'); ?></td>
            </tr>
            <tr>
                <th>Email:</th>
                <td><?php echo htmlspecialchars($pelanggan['email'] ?? '-'); ?></td>
            </tr>
            <tr>
                <th>Total Servis:</th>
                <td><span class="dashboard-card"
                        style="display: inline-block; padding: 0.25rem 0.75rem; margin: 0;"><?php echo $totalServis; ?></span>
                </td>
            </tr>
        </table>
    </div>

    <div class="table-container">
        <h3>Servis Aktif</h3>
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
                if (mysqli_num_rows($servisAktifResult) > 0) {
                    while ($row = mysqli_fetch_assoc($servisAktifResult)) {
                        echo "<tr>";
                        echo "<td>" . $row['id_servis'] . "</td>";
                        echo "<td>" . date('d/m/Y H:i', strtotime($row['tanggal_servis'])) . "</td>";
                        echo "<td>" . htmlspecialchars($row['jenis_kendaraan']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['no_plat']) . "</td>";
                        echo "<td>" . $row['status'] . "</td>";
                        echo "<td>Rp " . number_format($row['total_biaya'], 0, ',', '.') . "</td>";
                        echo "<td><a href='riwayat.php?id=" . $row['id_servis'] . "' class='btn btn-view'>Detail</a></td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='7'>Tidak ada servis aktif</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <div class="table-container">
        <h3>Riwayat Servis Terakhir</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tanggal</th>
                    <th>Kendaraan</th>
                    <th>No. Plat</th>
                    <th>Total Biaya</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (mysqli_num_rows($riwayatServisResult) > 0) {
                    while ($row = mysqli_fetch_assoc($riwayatServisResult)) {
                        echo "<tr>";
                        echo "<td>" . $row['id_servis'] . "</td>";
                        echo "<td>" . date('d/m/Y H:i', strtotime($row['tanggal_servis'])) . "</td>";
                        echo "<td>" . htmlspecialchars($row['jenis_kendaraan']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['no_plat']) . "</td>";
                        echo "<td>Rp " . number_format($row['total_biaya'], 0, ',', '.') . "</td>";
                        echo "<td><a href='riwayat.php?id=" . $row['id_servis'] . "' class='btn btn-view'>Detail</a></td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='6'>Belum ada riwayat servis</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <div style="margin-top: 1rem;">
            <a href="riwayat.php" class="btn btn-primary">Lihat Semua Riwayat</a>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>