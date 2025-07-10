<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

requireRole('admin');

$pageTitle = "Laporan";
include '../../includes/header.php';

// Filter tanggal
$tanggal_mulai = isset($_GET['tanggal_mulai']) ? $_GET['tanggal_mulai'] : date('Y-m-01');
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-d');

// Filter jenis laporan
$jenis_laporan = isset($_GET['jenis_laporan']) ? $_GET['jenis_laporan'] : 'servis';

// Debug untuk melihat nilai parameter
// echo "<pre>Tanggal Mulai: $tanggal_mulai\nTanggal Akhir: $tanggal_akhir\nJenis Laporan: $jenis_laporan</pre>";
?>

<div class="content-container">
    <h2>Laporan</h2>

    <!-- Form Filter -->
    <div class="form-container">
        <form method="get" action="">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <div class="form-group">
                    <label for="jenis_laporan">Jenis Laporan:</label>
                    <select id="jenis_laporan" name="jenis_laporan" onchange="this.form.submit()">
                        <option value="servis" <?php echo ($jenis_laporan == 'servis') ? 'selected' : ''; ?>>Servis
                        </option>
                        <option value="pendapatan" <?php echo ($jenis_laporan == 'pendapatan') ? 'selected' : ''; ?>>
                            Pendapatan</option>
                        <option value="sparepart" <?php echo ($jenis_laporan == 'sparepart') ? 'selected' : ''; ?>>
                            Penggunaan Sparepart</option>
                    </select>
                </div>

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

    <!-- Tampilkan Laporan -->
    <div class="table-container">
        <?php if ($jenis_laporan == 'servis'): ?>
        <h3>Laporan Servis (<?php echo date('d/m/Y', strtotime($tanggal_mulai)); ?> -
            <?php echo date('d/m/Y', strtotime($tanggal_akhir)); ?>)</h3>

        <?php
            // Hitung total dan rata-rata
            $totalQuery = "SELECT 
                            COUNT(*) as total_servis,
                            SUM(total_biaya) as total_pendapatan,
                            AVG(total_biaya) as rata_biaya
                          FROM servis
                          WHERE DATE(tanggal_servis) BETWEEN ? AND ?";
            $totalStmt = mysqli_prepare($conn, $totalQuery);
            
            // Tambahkan pengecekan error
            if (!$totalStmt) {
                echo "<div class='error-message'>Error preparing statement: " . mysqli_error($conn) . "</div>";
            } else {
                mysqli_stmt_bind_param($totalStmt, "ss", $tanggal_mulai, $tanggal_akhir);
                
                // Tambahkan pengecekan error
                if (!mysqli_stmt_execute($totalStmt)) {
                    echo "<div class='error-message'>Error executing statement: " . mysqli_stmt_error($totalStmt) . "</div>";
                } else {
                    $totalResult = mysqli_stmt_get_result($totalStmt);
                    $totalData = mysqli_fetch_assoc($totalResult);
            ?>

        <div
            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
            <div class="dashboard-card" style="margin-bottom: 0;">
                <h3>Total Servis</h3>
                <p><?php echo $totalData['total_servis']; ?></p>
            </div>

            <div class="dashboard-card" style="margin-bottom: 0;">
                <h3>Total Pendapatan</h3>
                <p>Rp
                    <?php echo number_format($totalData['total_pendapatan'] ?? 0, 0, ',', '.'); ?></p>
            </div>

            <div class="dashboard-card" style="margin-bottom: 0;">
                <h3>Rata-rata Biaya</h3>
                <p>Rp
                    <?php echo number_format($totalData['rata_biaya'] ?? 0, 0, ',', '.'); ?></p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tanggal</th>
                    <th>Pelanggan</th>
                    <th>Kendaraan</th>
                    <th>Status</th>
                    <th>Mekanik</th>
                    <th>Total Biaya</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    $query = "SELECT s.*, p.nama as nama_pelanggan, m.nama as nama_mekanik
                              FROM servis s
                              JOIN pelanggan p ON s.id_pelanggan = p.id_pelanggan
                              LEFT JOIN mekanik m ON s.id_mekanik = m.id_mekanik
                              WHERE DATE(s.tanggal_servis) BETWEEN ? AND ?
                              ORDER BY s.tanggal_servis DESC";
                    $stmt = mysqli_prepare($conn, $query);
                    
                    // Tambahkan pengecekan error
                    if (!$stmt) {
                        echo "<tr><td colspan='8'>Error preparing statement: " . mysqli_error($conn) . "</td></tr>";
                    } else {
                        mysqli_stmt_bind_param($stmt, "ss", $tanggal_mulai, $tanggal_akhir);
                        
                        // Tambahkan pengecekan error
                        if (!mysqli_stmt_execute($stmt)) {
                            echo "<tr><td colspan='8'>Error executing statement: " . mysqli_stmt_error($stmt) . "</td></tr>";
                        } else {
                            $result = mysqli_stmt_get_result($stmt);
                            
                            if (mysqli_num_rows($result) > 0) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    echo "<tr>";
                                    echo "<td>" . $row['id_servis'] . "</td>";
                                    echo "<td>" . date('d/m/Y H:i', strtotime($row['tanggal_servis'])) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['nama_pelanggan']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['jenis_kendaraan']) . " (" . htmlspecialchars($row['no_plat']) . ")</td>";
                                    echo "<td>" . $row['status'] . "</td>";
                                    echo "<td>" . htmlspecialchars($row['nama_mekanik'] ?? 'Belum ditugaskan') . "</td>";
                                    echo "<td>Rp " . number_format($row['total_biaya'], 0, ',', '.') . "</td>";
                                    echo "<td><a href='../kasir/servis.php?id=" . $row['id_servis'] . "' class='btn btn-view'>Detail</a></td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='8'>Tidak ada data servis pada periode tersebut</td></tr>";
                            }
                        }
                    }
                    ?>
            </tbody>
        </table>
        <?php
                }
            }
            ?>

        <?php elseif ($jenis_laporan == 'pendapatan'): ?>
        <h3>Laporan Pendapatan (<?php echo date('d/m/Y', strtotime($tanggal_mulai)); ?> -
            <?php echo date('d/m/Y', strtotime($tanggal_akhir)); ?>)</h3>

        <?php
            // Hitung total pendapatan
            $totalPendapatanQuery = "SELECT SUM(total_biaya) as total FROM servis WHERE DATE(tanggal_servis) BETWEEN ? AND ? AND status = 'Diambil'";
            $totalPendapatanStmt = mysqli_prepare($conn, $totalPendapatanQuery);
            
            // Tambahkan pengecekan error
            if (!$totalPendapatanStmt) {
                echo "<div class='error-message'>Error preparing statement: " . mysqli_error($conn) . "</div>";
            } else {
                mysqli_stmt_bind_param($totalPendapatanStmt, "ss", $tanggal_mulai, $tanggal_akhir);
                
                // Tambahkan pengecekan error
                if (!mysqli_stmt_execute($totalPendapatanStmt)) {
                    echo "<div class='error-message'>Error executing statement: " . mysqli_stmt_error($totalPendapatanStmt) . "</div>";
                } else {
                    $totalPendapatanResult = mysqli_stmt_get_result($totalPendapatanStmt);
                    $totalPendapatan = mysqli_fetch_assoc($totalPendapatanResult)['total'] ?? 0;
            ?>

        <div class="dashboard-card" style="margin-bottom: 1.5rem;">
            <h3>Total Pendapatan</h3>
            <p>Rp <?php echo number_format($totalPendapatan, 0, ',', '.'); ?></p>
        </div>

        <h4>Pendapatan per Hari</h4>
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Jumlah Servis</th>
                    <th>Total Pendapatan</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    $pendapatanHarianQuery = "SELECT 
                                              DATE(tanggal_servis) as tanggal,
                                              COUNT(*) as jumlah_servis,
                                              SUM(total_biaya) as total_pendapatan
                                              FROM servis
                                              WHERE DATE(tanggal_servis) BETWEEN ? AND ? AND status = 'Diambil'
                                              GROUP BY DATE(tanggal_servis)
                                              ORDER BY tanggal DESC";
                    $pendapatanHarianStmt = mysqli_prepare($conn, $pendapatanHarianQuery);
                    
                    // Tambahkan pengecekan error
                    if (!$pendapatanHarianStmt) {
                        echo "<tr><td colspan='3'>Error preparing statement: " . mysqli_error($conn) . "</td></tr>";
                    } else {
                        mysqli_stmt_bind_param($pendapatanHarianStmt, "ss", $tanggal_mulai, $tanggal_akhir);
                        
                        // Tambahkan pengecekan error
                        if (!mysqli_stmt_execute($pendapatanHarianStmt)) {
                            echo "<tr><td colspan='3'>Error executing statement: " . mysqli_stmt_error($pendapatanHarianStmt) . "</td></tr>";
                        } else {
                            $pendapatanHarianResult = mysqli_stmt_get_result($pendapatanHarianStmt);
                            
                            if (mysqli_num_rows($pendapatanHarianResult) > 0) {
                                while ($row = mysqli_fetch_assoc($pendapatanHarianResult)) {
                                    echo "<tr>";
                                    echo "<td>" . date('d/m/Y', strtotime($row['tanggal'])) . "</td>";
                                    echo "<td>" . $row['jumlah_servis'] . "</td>";
                                    echo "<td>Rp " . number_format($row['total_pendapatan'], 0, ',', '.') . "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='3'>Tidak ada data pendapatan pada periode tersebut</td></tr>";
                            }
                        }
                    }
                    ?>
            </tbody>
        </table>
        <?php
                }
            }
            ?>

        <?php elseif ($jenis_laporan == 'sparepart'): ?>
        <h3>Laporan Penggunaan Sparepart (<?php echo date('d/m/Y', strtotime($tanggal_mulai)); ?> -
            <?php echo date('d/m/Y', strtotime($tanggal_akhir)); ?>)</h3>

        <table>
            <thead>
                <tr>
                    <th>Nama Sparepart</th>
                    <th>Jumlah Terpakai</th>
                    <th>Total Nilai</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    $sparepartQuery = "SELECT 
                                      s.nama_sparepart,
                                      SUM(d.jumlah) as jumlah_terpakai,
                                      SUM(d.subtotal) as total_nilai
                                      FROM detail_servis d
                                      JOIN sparepart s ON d.id_sparepart = s.id_sparepart
                                      JOIN servis sv ON d.id_servis = sv.id_servis
                                      WHERE DATE(sv.tanggal_servis) BETWEEN ? AND ?
                                      AND d.id_sparepart IS NOT NULL
                                      GROUP BY s.id_sparepart
                                      ORDER BY jumlah_terpakai DESC";
                    $sparepartStmt = mysqli_prepare($conn, $sparepartQuery);
                    
                    // Tambahkan pengecekan error
                    if (!$sparepartStmt) {
                        echo "<tr><td colspan='3'>Error preparing statement: " . mysqli_error($conn) . "</td></tr>";
                    } else {
                        mysqli_stmt_bind_param($sparepartStmt, "ss", $tanggal_mulai, $tanggal_akhir);
                        
                        // Tambahkan pengecekan error
                        if (!mysqli_stmt_execute($sparepartStmt)) {
                            echo "<tr><td colspan='3'>Error executing statement: " . mysqli_stmt_error($sparepartStmt) . "</td></tr>";
                        } else {
                            $sparepartResult = mysqli_stmt_get_result($sparepartStmt);
                            
                            if (mysqli_num_rows($sparepartResult) > 0) {
                                while ($row = mysqli_fetch_assoc($sparepartResult)) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['nama_sparepart']) . "</td>";
                                    echo "<td>" . $row['jumlah_terpakai'] . "</td>";
                                    echo "<td>Rp " . number_format($row['total_nilai'], 0, ',', '.') . "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='3'>Tidak ada data penggunaan sparepart pada periode tersebut</td></tr>";
                            }
                        }
                    }
                    ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <div style="margin-top: 1.5rem;">
        <button onclick="window.print()" class="btn btn-primary">Cetak Laporan</button>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>