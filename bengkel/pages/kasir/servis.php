<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

requireRole('kasir');

$pageTitle = "Manajemen Servis";
include '../../includes/header.php';

// Proses form tambah servis baru
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add_servis') {
        $id_pelanggan = $_POST['id_pelanggan'];
        $id_mekanik = $_POST['id_mekanik'];
        $jenis_kendaraan = $_POST['jenis_kendaraan'];
        $no_plat = $_POST['no_plat'];
        $keluhan = $_POST['keluhan'];
        
        // Menggunakan stored procedure untuk menambah servis
        $query = "CALL sp_tambah_servis(?, ?, ?, ?, ?, @id_servis)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "iisss", $id_pelanggan, $id_mekanik, $jenis_kendaraan, $no_plat, $keluhan);
        
        if (mysqli_stmt_execute($stmt)) {
            // Ambil ID servis yang baru dibuat
            $result = mysqli_query($conn, "SELECT @id_servis as id_servis");
            $row = mysqli_fetch_assoc($result);
            $id_servis = $row['id_servis'];
            
            echo "<div class='success-message'>Servis berhasil ditambahkan dengan ID: " . $id_servis . "</div>";
            echo "<script>window.location.href = 'servis.php?id=" . $id_servis . "';</script>";
        } else {
            echo "<div class='error-message'>Error: " . mysqli_error($conn) . "</div>";
        }
    }
    
    // Proses tambah detail servis
    if ($_POST['action'] == 'add_detail') {
        $id_servis = $_POST['id_servis'];
        $id_sparepart = !empty($_POST['id_sparepart']) ? $_POST['id_sparepart'] : NULL;
        $nama_jasa = !empty($_POST['nama_jasa']) ? $_POST['nama_jasa'] : NULL;
        $jumlah = $_POST['jumlah'];
        $harga_satuan = $_POST['harga_satuan'];
        
        // Menggunakan stored procedure untuk menambah detail servis
        $query = "CALL sp_tambah_detail_servis(?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "iisid", $id_servis, $id_sparepart, $nama_jasa, $jumlah, $harga_satuan);
        
        if (mysqli_stmt_execute($stmt)) {
            echo "<div class='success-message'>Detail servis berhasil ditambahkan</div>";
        } else {
            echo "<div class='error-message'>Error: " . mysqli_error($conn) . "</div>";
        }
    }
    
    // Proses update status servis
    if ($_POST['action'] == 'update_status') {
        $id_servis = $_POST['id_servis'];
        $status = $_POST['status'];
        
        // Menggunakan stored procedure untuk update status
        $query = "CALL sp_update_status_servis(?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "is", $id_servis, $status);
        
        if (mysqli_stmt_execute($stmt)) {
            echo "<div class='success-message'>Status servis berhasil diperbarui</div>";
        } else {
            echo "<div class='error-message'>Error: " . mysqli_error($conn) . "</div>";
        }
    }
}

// Proses hapus detail servis
if (isset($_GET['delete_detail'])) {
    $id_detail = $_GET['delete_detail'];
    $id_servis = $_GET['id'];
    
    $query = "DELETE FROM detail_servis WHERE id_detail = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $id_detail);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "<div class='success-message'>Detail servis berhasil dihapus</div>";
    } else {
        echo "<div class='error-message'>Error: " . mysqli_error($conn) . "</div>";
    }
}
?>

<div class="content-container">
    <?php if (!isset($_GET['id'])): ?>
    <!-- Tampilkan daftar servis aktif -->
    <h2>Daftar Servis Aktif</h2>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Pelanggan</th>
                    <th>No. Plat</th>
                    <th>Jenis Kendaraan</th>
                    <th>Tanggal</th>
                    <th>Status</th>
                    <th>Mekanik</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Menggunakan view untuk menampilkan servis aktif
                $query = "SELECT * FROM v_servis_aktif ORDER BY tanggal_servis DESC";
                $result = mysqli_query($conn, $query);
                
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo "<tr>";
                        echo "<td>" . $row['id_servis'] . "</td>";
                        echo "<td>" . htmlspecialchars($row['nama_pelanggan']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['no_plat']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['jenis_kendaraan']) . "</td>";
                        echo "<td>" . date('d/m/Y H:i', strtotime($row['tanggal_servis'])) . "</td>";
                        echo "<td>" . $row['status'] . "</td>";
                        echo "<td>" . htmlspecialchars($row['nama_mekanik'] ?? 'Belum ditugaskan') . "</td>";
                        echo "<td>
                                <a href='?id=" . $row['id_servis'] . "' class='btn btn-view'>Detail</a>
                              </td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='8'>Tidak ada servis aktif</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- Form Tambah Servis Baru -->
    <div class="form-container">
        <h3>Tambah Servis Baru</h3>
        <form method="post" action="">
            <input type="hidden" name="action" value="add_servis">

            <div class="form-group">
                <label for="id_pelanggan">Pelanggan:</label>
                <select id="id_pelanggan" name="id_pelanggan" required>
                    <option value="">-- Pilih Pelanggan --</option>
                    <?php
                    $pelangganQuery = "SELECT id_pelanggan, nama FROM pelanggan ORDER BY nama";
                    $pelangganResult = mysqli_query($conn, $pelangganQuery);
                    
                    while ($pelanggan = mysqli_fetch_assoc($pelangganResult)) {
                        echo "<option value='" . $pelanggan['id_pelanggan'] . "'>" . htmlspecialchars($pelanggan['nama']) . "</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label for="id_mekanik">Mekanik:</label>
                <select id="id_mekanik" name="id_mekanik">
                    <option value="">-- Pilih Mekanik --</option>
                    <?php
                    $mekanikQuery = "SELECT id_mekanik, nama, spesialisasi FROM mekanik ORDER BY nama";
                    $mekanikResult = mysqli_query($conn, $mekanikQuery);
                    
                    while ($mekanik = mysqli_fetch_assoc($mekanikResult)) {
                        echo "<option value='" . $mekanik['id_mekanik'] . "'>" . htmlspecialchars($mekanik['nama']) . " (" . htmlspecialchars($mekanik['spesialisasi']) . ")</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label for="jenis_kendaraan">Jenis Kendaraan:</label>
                <input type="text" id="jenis_kendaraan" name="jenis_kendaraan" required>
            </div>

            <div class="form-group">
                <label for="no_plat">Nomor Plat:</label>
                <input type="text" id="no_plat" name="no_plat" required>
            </div>

            <div class="form-group">
                <label for="keluhan">Keluhan:</label>
                <textarea id="keluhan" name="keluhan" rows="3" required></textarea>
            </div>

            <button type="submit" class="btn btn-primary">Tambah Servis</button>
        </form>
    </div>

    <?php else: ?>
    <!-- Tampilkan detail servis -->
    <?php
    $id_servis = $_GET['id'];
    
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
    
    if (!$servis) {
        echo "<div class='error-message'>Servis tidak ditemukan</div>";
        echo "<a href='servis.php' class='btn btn-secondary'>Kembali</a>";
    } else {
    ?>
    <h2>Detail Servis #<?php echo $id_servis; ?></h2>

    <div class="detail-container">
        <div class="detail-section">
            <h3>Informasi Servis</h3>
            <table class="detail-table">
                <tr>
                    <th>Pelanggan:</th>
                    <td><?php echo htmlspecialchars($servis['nama_pelanggan']); ?></td>
                </tr>
                <tr>
                    <th>No. Telepon:</th>
                    <td><?php echo htmlspecialchars($servis['no_telepon']); ?></td>
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
                    <th>Tanggal Servis:</th>
                    <td><?php echo date('d/m/Y H:i', strtotime($servis['tanggal_servis'])); ?></td>
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
                    <th>Status:</th>
                    <td><?php echo $servis['status']; ?></td>
                </tr>
                <tr>
                    <th>Total Biaya:</th>
                    <td>Rp <?php echo number_format($servis['total_biaya'], 0, ',', '.'); ?></td>
                </tr>
            </table>

            <!-- Form Update Status -->
            <div class="form-container">
                <h4>Update Status</h4>
                <form method="post" action="">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="id_servis" value="<?php echo $id_servis; ?>">

                    <div class="form-group">
                        <label for="status">Status:</label>
                        <select id="status" name="status" required>
                            <option value="Menunggu" <?php echo ($servis['status'] == 'Menunggu') ? 'selected' : ''; ?>>
                                Menunggu</option>
                            <option value="Proses" <?php echo ($servis['status'] == 'Proses') ? 'selected' : ''; ?>>
                                Proses</option>
                            <option value="Selesai" <?php echo ($servis['status'] == 'Selesai') ? 'selected' : ''; ?>>
                                Selesai</option>
                            <option value="Diambil" <?php echo ($servis['status'] == 'Diambil') ? 'selected' : ''; ?>>
                                Diambil</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">Update Status</button>
                </form>
            </div>
        </div>

        <div class="detail-section">
            <h3>Detail Item dan Jasa</h3>

            <!-- Tabel Detail Servis -->
            <table>
                <thead>
                    <tr>
                        <th>Item/Jasa</th>
                        <th>Jumlah</th>
                        <th>Harga Satuan</th>
                        <th>Subtotal</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $detailQuery = "SELECT d.*, s.nama_sparepart 
                                    FROM detail_servis d
                                    LEFT JOIN sparepart s ON d.id_sparepart = s.id_sparepart
                                    WHERE d.id_servis = ?";
                    $detailStmt = mysqli_prepare($conn, $detailQuery);
                    mysqli_stmt_bind_param($detailStmt, "i", $id_servis);
                    mysqli_stmt_execute($detailStmt);
                    $detailResult = mysqli_stmt_get_result($detailStmt);
                    
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
                            echo "<td>
                                    <a href='?id=" . $id_servis . "&delete_detail=" . $detail['id_detail'] . "' class='btn btn-delete' onclick='return confirm(\"Yakin ingin menghapus item ini?\")'>Hapus</a>
                                  </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5'>Belum ada item atau jasa</td></tr>";
                    }
                    ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3">Total</th>
                        <th>Rp <?php echo number_format($servis['total_biaya'], 0, ',', '.'); ?></th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>

            <!-- Form Tambah Detail Servis -->
            <div class="form-container">
                <h4>Tambah Item/Jasa</h4>
                <form method="post" action="">
                    <input type="hidden" name="action" value="add_detail">
                    <input type="hidden" name="id_servis" value="<?php echo $id_servis; ?>">

                    <div class="form-group">
                        <label for="tipe_item">Tipe:</label>
                        <select id="tipe_item" onchange="toggleItemForm()">
                            <option value="sparepart">Sparepart</option>
                            <option value="jasa">Jasa</option>
                        </select>
                    </div>

                    <div id="sparepart_form">
                        <div class="form-group">
                            <label for="id_sparepart">Sparepart:</label>
                            <select id="id_sparepart" name="id_sparepart">
                                <option value="">-- Pilih Sparepart --</option>
                                <?php
                                $sparepartQuery = "SELECT id_sparepart, nama_sparepart, harga, stok FROM sparepart WHERE stok > 0 ORDER BY nama_sparepart";
                                $sparepartResult = mysqli_query($conn, $sparepartQuery);
                                
                                while ($sparepart = mysqli_fetch_assoc($sparepartResult)) {
                                    echo "<option value='" . $sparepart['id_sparepart'] . "' data-harga='" . $sparepart['harga'] . "' data-stok='" . $sparepart['stok'] . "'>" 
                                        . htmlspecialchars($sparepart['nama_sparepart']) 
                                        . " (Stok: " . $sparepart['stok'] . ", Harga: Rp " . number_format($sparepart['harga'], 0, ',', '.') . ")</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div id="jasa_form" style="display: none;">
                        <div class="form-group">
                            <label for="nama_jasa">Nama Jasa:</label>
                            <input type="text" id="nama_jasa" name="nama_jasa">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="jumlah">Jumlah:</label>
                        <input type="number" id="jumlah" name="jumlah" min="1" value="1" required>
                    </div>

                    <div class="form-group">
                        <label for="harga_satuan">Harga Satuan:</label>
                        <input type="number" id="harga_satuan" name="harga_satuan" min="0" step="1000" required>
                    </div>

                    <button type="submit" class="btn btn-primary">Tambah Item/Jasa</button>
                </form>
            </div>
        </div>
    </div>

    <div class="action-buttons">
        <a href="servis.php" class="btn btn-secondary">Kembali ke Daftar Servis</a>
        <?php if ($servis['status'] == 'Selesai' || $servis['status'] == 'Diambil'): ?>
        <a href="transaksi.php?print=<?php echo $id_servis; ?>" class="btn btn-primary">Cetak Nota</a>
        <?php endif; ?>
    </div>

    <script>
    function toggleItemForm() {
        var tipeItem = document.getElementById('tipe_item').value;

        if (tipeItem === 'sparepart') {
            document.getElementById('sparepart_form').style.display = 'block';
            document.getElementById('jasa_form').style.display = 'none';
            document.getElementById('nama_jasa').value = '';
        } else {
            document.getElementById('sparepart_form').style.display = 'none';
            document.getElementById('jasa_form').style.display = 'block';
            document.getElementById('id_sparepart').value = '';
        }
    }

    // Auto-fill harga saat memilih sparepart
    document.getElementById('id_sparepart').addEventListener('change', function() {
        var selectedOption = this.options[this.selectedIndex];
        if (selectedOption.value) {
            document.getElementById('harga_satuan').value = selectedOption.getAttribute('data-harga');
        }
    });
    </script>
    <?php
    }
    ?>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>