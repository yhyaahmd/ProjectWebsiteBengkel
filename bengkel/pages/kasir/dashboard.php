<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

requireRole('kasir');

$pageTitle = "Dashboard Kasir";
include '../../includes/header.php';

// Mengambil statistik untuk dashboard
$servisHariIniQuery = "SELECT COUNT(*) as total FROM servis WHERE DATE(tanggal_servis) = CURDATE()";
$servisHariIniResult = mysqli_query($conn, $servisHariIniQuery);
$servisHariIni = mysqli_fetch_assoc($servisHariIniResult)['total'];

$servisProsesQuery = "SELECT COUNT(*) as total FROM servis WHERE status = 'Proses'";
$servisProsesResult = mysqli_query($conn, $servisProsesQuery);
$servisProses = mysqli_fetch_assoc($servisProsesResult)['total'];

$servisSelesaiQuery = "SELECT COUNT(*) as total FROM servis WHERE status = 'Selesai'";
$servisSelesaiResult = mysqli_query($conn, $servisSelesaiQuery);
$servisSelesai = mysqli_fetch_assoc($servisSelesaiResult)['total'];

$pendapatanHariIniQuery = "SELECT SUM(total_biaya) as total FROM servis WHERE DATE(tanggal_servis) = CURDATE() AND status = 'Diambil'";
$pendapatanHariIniResult = mysqli_query($conn, $pendapatanHariIniQuery);
$pendapatanHariIni = mysqli_fetch_assoc($pendapatanHariIniResult)['total'] ?? 0;

// Mengambil servis terbaru
$servisTerbaruQuery = "SELECT s.id_servis, s.tanggal_servis, s.status, p.nama as nama_pelanggan, s.no_plat, s.jenis_kendaraan
                      FROM servis s
                      JOIN pelanggan p ON s.id_pelanggan = p.id_pelanggan
                      ORDER BY s.tanggal_servis DESC
                      LIMIT 5";
$servisTerbaruResult = mysqli_query($conn, $servisTerbaruQuery);

// Mengambil sparepart dengan stok menipis
$stokMenipisQuery = "SELECT * FROM v_stok_menipis LIMIT 5";
$stokMenipisResult = mysqli_query($conn, $stokMenipisQuery);
?>

<div class="content-container">
    <h2>Dashboard Kasir</h2>

    <div class="dashboard-cards">
        <div class="dashboard-card">
            <h3>Servis Hari Ini</h3>
            <p><?php echo $servisHariIni; ?></p>
            <a href="servis.php" class="btn btn-primary">Lihat Servis</a>
        </div>

        <div class="dashboard-card">
            <h3>Servis Dalam Proses</h3>
            <p><?php echo $servisProses; ?></p>
            <a href="servis.php" class="btn btn-primary">Lihat Servis</a>
        </div>

        <div class="dashboard-card">
            <h3>Servis Selesai</h3>
            <p><?php echo $servisSelesai; ?></p>
            <a href="servis.php" class="btn btn-primary">Lihat Servis</a>
        </div>

        <div class="dashboard-card">
            <h3>Pendapatan Hari Ini</h3>
            <p>Rp <?php echo number_format($pendapatanHariIni, 0, ',', '.'); ?></p>
            <a href="transaksi.php" class="btn btn-primary">Lihat Transaksi</a>
        </div>
    </div>

    <!-- Ensure tables are responsive -->
    <style>
    .dashboard-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }

    @media (min-width: 1200px) {
        .dashboard-grid {
            grid-template-columns: 1fr 1fr;
        }
    }

    .table-container {
        overflow-x: auto;
    }

    .table-container table {
        min-width: 100%;
    }
    </style>

    <div class="dashboard-grid">
        <div class="table-container">
            <h3>Servis Terbaru</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Pelanggan</th>
                        <th>Kendaraan</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (mysqli_num_rows($servisTerbaruResult) > 0) {
                        while ($row = mysqli_fetch_assoc($servisTerbaruResult)) {
                            echo "<tr>";
                            echo "<td>" . $row['id_servis'] . "</td>";
                            echo "<td>" . htmlspecialchars($row['nama_pelanggan']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['jenis_kendaraan']) . " (" . htmlspecialchars($row['no_plat']) . ")</td>";
                            echo "<td>" . date('d/m/Y H:i', strtotime($row['tanggal_servis'])) . "</td>";
                            echo "<td>" . $row['status'] . "</td>";
                            echo "<td><a href='servis.php?id=" . $row['id_servis'] . "' class='btn btn-view'>Detail</a></td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6'>Tidak ada data servis terbaru</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="table-container">
            <h3>Sparepart Stok Menipis</h3>
            <table>
                <thead>
                    <tr>
                        <th>Nama Sparepart</th>
                        <th>Stok</th>
                        <th>Harga</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (mysqli_num_rows($stokMenipisResult) > 0) {
                        while ($row = mysqli_fetch_assoc($stokMenipisResult)) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['nama_sparepart']) . "</td>";
                            echo "<td>" . $row['stok'] . "</td>";
                            echo "<td>Rp " . number_format($row['harga'], 0, ',', '.') . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='3'>Tidak ada sparepart dengan stok menipis</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="action-buttons" style="margin-top: 2rem;">
        <a href="servis.php" class="btn btn-primary">Tambah Servis Baru</a>
        <a href="transaksi.php" class="btn btn-secondary">Lihat Transaksi</a>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>