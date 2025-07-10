<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

requireRole('admin');

$pageTitle = "Manajemen Sparepart";
include '../../includes/header.php';

// Proses form tambah/edit sparepart
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        // Tambah sparepart baru
        if ($_POST['action'] == 'add') {
            $nama = $_POST['nama_sparepart'];
            $harga = $_POST['harga'];
            $stok = $_POST['stok'];
            $kategori = $_POST['kategori'];
            $deskripsi = $_POST['deskripsi'];
            
            $query = "INSERT INTO sparepart (nama_sparepart, harga, stok, kategori, deskripsi) 
                      VALUES (?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "sdiss", $nama, $harga, $stok, $kategori, $deskripsi);
            
            if (mysqli_stmt_execute($stmt)) {
                echo "<div class='success-message'>Sparepart berhasil ditambahkan</div>";
            } else {
                echo "<div class='error-message'>Error: " . mysqli_error($conn) . "</div>";
            }
        }
        
        // Edit sparepart
        if ($_POST['action'] == 'edit') {
            $id = $_POST['id_sparepart'];
            $nama = $_POST['nama_sparepart'];
            $harga = $_POST['harga'];
            $stok = $_POST['stok'];
            $kategori = $_POST['kategori'];
            $deskripsi = $_POST['deskripsi'];
            
            $query = "UPDATE sparepart SET 
                      nama_sparepart = ?, 
                      harga = ?, 
                      stok = ?, 
                      kategori = ?, 
                      deskripsi = ? 
                      WHERE id_sparepart = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "sdissi", $nama, $harga, $stok, $kategori, $deskripsi, $id);
            
            if (mysqli_stmt_execute($stmt)) {
                echo "<div class='success-message'>Sparepart berhasil diperbarui</div>";
                echo "<script>window.location.href = 'sparepart.php';</script>"; // Redirect setelah update berhasil
            } else {
                echo "<div class='error-message'>Error: " . mysqli_error($conn) . "</div>";
            }
        }
    }
}

// Proses hapus sparepart
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Periksa apakah sparepart digunakan dalam detail_servis
    $checkQuery = "SELECT COUNT(*) as count FROM detail_servis WHERE id_sparepart = ?";
    $checkStmt = mysqli_prepare($conn, $checkQuery);
    mysqli_stmt_bind_param($checkStmt, "i", $id);
    mysqli_stmt_execute($checkStmt);
    $checkResult = mysqli_stmt_get_result($checkStmt);
    $checkData = mysqli_fetch_assoc($checkResult);
    
    if ($checkData['count'] > 0) {
        echo "<div class='error-message'>Sparepart tidak dapat dihapus karena sedang digunakan dalam transaksi</div>";
    } else {
        $query = "DELETE FROM sparepart WHERE id_sparepart = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $id);
        
        if (mysqli_stmt_execute($stmt)) {
            echo "<div class='success-message'>Sparepart berhasil dihapus</div>";
        } else {
            echo "<div class='error-message'>Error: " . mysqli_error($conn) . "</div>";
        }
    }
}

// Ambil data sparepart untuk ditampilkan
$query = "SELECT * FROM sparepart ORDER BY nama_sparepart";
$result = mysqli_query($conn, $query);
?>

<div class="content-container">
    <h2>Manajemen Sparepart</h2>

    <!-- Form Tambah Sparepart -->
    <div class="form-container">
        <h3>Tambah Sparepart Baru</h3>
        <form method="post" action="">
            <input type="hidden" name="action" value="add">

            <div class="form-group">
                <label for="nama_sparepart">Nama Sparepart:</label>
                <input type="text" id="nama_sparepart" name="nama_sparepart" required>
            </div>

            <div class="form-group">
                <label for="harga">Harga:</label>
                <input type="number" id="harga" name="harga" min="0" step="1000" required>
            </div>

            <div class="form-group">
                <label for="stok">Stok:</label>
                <input type="number" id="stok" name="stok" min="0" required>
            </div>

            <div class="form-group">
                <label for="kategori">Kategori:</label>
                <input type="text" id="kategori" name="kategori">
            </div>

            <div class="form-group">
                <label for="deskripsi">Deskripsi:</label>
                <textarea id="deskripsi" name="deskripsi" rows="3"></textarea>
            </div>

            <button type="submit" class="btn btn-primary">Tambah Sparepart</button>
        </form>
    </div>

    <!-- Tabel Daftar Sparepart -->
    <div class="table-container">
        <h3>Daftar Sparepart</h3>

        <!-- Tampilkan stok menipis dari view -->
        <div class="alert-box">
            <h4>Sparepart dengan Stok Menipis</h4>
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
                    $stokMenipisQuery = "SELECT * FROM v_stok_menipis";
                    $stokMenipisResult = mysqli_query($conn, $stokMenipisQuery);
                    
                    if (mysqli_num_rows($stokMenipisResult) > 0) {
                        while ($row = mysqli_fetch_assoc($stokMenipisResult)) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['nama_sparepart']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['stok']) . "</td>";
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

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama Sparepart</th>
                    <th>Harga</th>
                    <th>Stok</th>
                    <th>Kategori</th>
                    <th>Deskripsi</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo "<tr>";
                        echo "<td>" . $row['id_sparepart'] . "</td>";
                        echo "<td>" . htmlspecialchars($row['nama_sparepart']) . "</td>";
                        echo "<td>Rp " . number_format($row['harga'], 0, ',', '.') . "</td>";
                        echo "<td>" . $row['stok'] . "</td>";
                        echo "<td>" . htmlspecialchars($row['kategori']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['deskripsi']) . "</td>";
                        echo "<td>
                                <a href='?edit=" . $row['id_sparepart'] . "' class='btn btn-edit'>Edit</a>
                                <a href='?delete=" . $row['id_sparepart'] . "' class='btn btn-delete' onclick='return confirm(\"Yakin ingin menghapus sparepart ini?\")'>Hapus</a>
                              </td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='7'>Tidak ada data sparepart</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- Form Edit Sparepart -->
    <?php
    if (isset($_GET['edit'])) {
        $id = $_GET['edit'];
        $editQuery = "SELECT * FROM sparepart WHERE id_sparepart = ?";
        $editStmt = mysqli_prepare($conn, $editQuery);
        mysqli_stmt_bind_param($editStmt, "i", $id);
        mysqli_stmt_execute($editStmt);
        $editResult = mysqli_stmt_get_result($editStmt);
        
        if ($editData = mysqli_fetch_assoc($editResult)) {
    ?>
    <div class="form-container">
        <h3>Edit Sparepart</h3>
        <form method="post" action="">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id_sparepart" value="<?php echo $editData['id_sparepart']; ?>">

            <div class="form-group">
                <label for="edit_nama_sparepart">Nama Sparepart:</label>
                <input type="text" id="edit_nama_sparepart" name="nama_sparepart"
                    value="<?php echo htmlspecialchars($editData['nama_sparepart']); ?>" required>
            </div>

            <div class="form-group">
                <label for="edit_harga">Harga:</label>
                <input type="number" id="edit_harga" name="harga" min="0" step="1000"
                    value="<?php echo $editData['harga']; ?>" required>
            </div>

            <div class="form-group">
                <label for="edit_stok">Stok:</label>
                <input type="number" id="edit_stok" name="stok" min="0" value="<?php echo $editData['stok']; ?>"
                    required>
            </div>

            <div class="form-group">
                <label for="edit_kategori">Kategori:</label>
                <input type="text" id="edit_kategori" name="kategori"
                    value="<?php echo htmlspecialchars($editData['kategori']); ?>">
            </div>

            <div class="form-group">
                <label for="edit_deskripsi">Deskripsi:</label>
                <textarea id="edit_deskripsi" name="deskripsi"
                    rows="3"><?php echo htmlspecialchars($editData['deskripsi']); ?></textarea>
            </div>

            <div class="action-buttons">
                <button type="submit" class="btn btn-primary">Update Sparepart</button>
                <a href="sparepart.php" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
    <?php
        }
    }
    ?>
</div>

<?php include '../../includes/footer.php'; ?>