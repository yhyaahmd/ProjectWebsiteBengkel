<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

requireRole('admin');

$pageTitle = "Dashboard Admin";
include '../../includes/header.php';

// Mengambil statistik untuk dashboard
$totalPelangganQuery = "SELECT COUNT(*) as total FROM pelanggan";
$totalPelangganResult = mysqli_query($conn, $totalPelangganQuery);
$totalPelanggan = mysqli_fetch_assoc($totalPelangganResult)['total'];

$totalMekanikQuery = "SELECT COUNT(*) as total FROM mekanik";
$totalMekanikResult = mysqli_query($conn, $totalMekanikQuery);
$totalMekanik = mysqli_fetch_assoc($totalMekanikResult)['total'];

$totalSparepartQuery = "SELECT COUNT(*) as total FROM sparepart";
$totalSparepartResult = mysqli_query($conn, $totalSparepartQuery);
$totalSparepart = mysqli_fetch_assoc($totalSparepartResult)['total'];

$totalServisQuery = "SELECT COUNT(*) as total FROM servis";
$totalServisResult = mysqli_query($conn, $totalServisQuery);
$totalServis = mysqli_fetch_assoc($totalServisResult)['total'];

$pendapatanQuery = "SELECT SUM(total_biaya) as total FROM servis WHERE status = 'Diambil'";
$pendapatanResult = mysqli_query($conn, $pendapatanQuery);
$pendapatan = mysqli_fetch_assoc($pendapatanResult)['total'] ?? 0;

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
    <h2>Dashboard Admin</h2>

    <div class="dashboard-cards">
        <div class="dashboard-card">
            <h3>Total Pelanggan</h3>
            <p><?php echo $totalPelanggan; ?></p>
            <a href="pelanggan.php" class="btn btn-primary">Lihat Detail</a>
        </div>

        <div class="dashboard-card">
            <h3>Total Mekanik</h3>
            <p><?php echo $totalMekanik; ?></p>
            <a href="mekanik.php" class="btn btn-primary">Lihat Detail</a>
        </div>

        <div class="dashboard-card">
            <h3>Total Sparepart</h3>
            <p><?php echo $totalSparepart; ?></p>
            <a href="sparepart.php" class="btn btn-primary">Lihat Detail</a>
        </div>

        <div class="dashboard-card">
            <h3>Total Servis</h3>
            <p><?php echo $totalServis; ?></p>
            <a href="laporan.php" class="btn btn-primary">Lihat Detail</a>
        </div>
    </div>

    <div class="dashboard-card" style="margin-bottom: 2rem;">
        <h3>Total Pendapatan</h3>
        <p>Rp <?php echo number_format($pendapatan, 0, ',', '.'); ?></p>
        <a href="laporan.php" class="btn btn-primary">Lihat Laporan</a>
    </div>

    <div style="display: grid; grid-template-columns: 1fr; gap: 1.5rem;">
        <div class="table-container">
            <h3>Servis Terbaru</h3>
            <div style="overflow-x: auto;">
                <table style="min-width: 800px;">
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
                                echo "<td><span class='px-2 py-1 text-xs rounded-full " . 
                                    ($row['status'] == 'Selesai' ? 'bg-green-100 text-green-800' : 
                                     ($row['status'] == 'Proses' ? 'bg-yellow-100 text-yellow-800' : 
                                      ($row['status'] == 'Diambil' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'))) . "'>" . 
                                    $row['status'] . "</span></td>";
                                echo "<td><a href='../kasir/servis.php?id=" . $row['id_servis'] . "' class='btn btn-view'>Detail</a></td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6'>Tidak ada data servis terbaru</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="table-container">
            <h3>Sparepart Stok Menipis</h3>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Nama Sparepart</th>
                            <th>Stok</th>
                            <th>Harga</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (mysqli_num_rows($stokMenipisResult) > 0) {
                            while ($row = mysqli_fetch_assoc($stokMenipisResult)) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['nama_sparepart']) . "</td>";
                                echo "<td><span class='px-2 py-1 text-xs rounded-full " . 
                                    ($row['stok'] <= 2 ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') . "'>" . 
                                    $row['stok'] . "</span></td>";
                                echo "<td>Rp " . number_format($row['harga'], 0, ',', '.') . "</td>";
                                echo "<td><a href='sparepart.php?edit=" . $row['id_sparepart'] . "' class='btn btn-edit'>Update</a></td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4'>Tidak ada sparepart dengan stok menipis</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>