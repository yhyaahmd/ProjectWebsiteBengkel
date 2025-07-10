<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

requireRole('admin');

$pageTitle = "Manajemen Mekanik";
include '../../includes/header.php';

// Proses form tambah/edit mekanik
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        // Tambah mekanik baru
        if ($_POST['action'] == 'add') {
            $nama = $_POST['nama'];
            $no_telepon = $_POST['no_telepon'];
            $alamat = $_POST['alamat'];
            $spesialisasi = $_POST['spesialisasi'];
            
            $query = "INSERT INTO mekanik (nama, no_telepon, alamat, spesialisasi) 
                      VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ssss", $nama, $no_telepon, $alamat, $spesialisasi);
            
            if (mysqli_stmt_execute($stmt)) {
                echo "<div class='success-message'>Mekanik berhasil ditambahkan</div>";
            } else {
                echo "<div class='error-message'>Error: " . mysqli_error($conn) . "</div>";
            }
        }
        
        // Edit mekanik
        if ($_POST['action'] == 'edit') {
            $id = $_POST['id_mekanik'];
            $nama = $_POST['nama'];
            $no_telepon = $_POST['no_telepon'];
            $alamat = $_POST['alamat'];
            $spesialisasi = $_POST['spesialisasi'];
            
            $query = "UPDATE mekanik SET 
                      nama = ?, 
                      no_telepon = ?, 
                      alamat = ?, 
                      spesialisasi = ? 
                      WHERE id_mekanik = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ssssi", $nama, $no_telepon, $alamat, $spesialisasi, $id);
            
            if (mysqli_stmt_execute($stmt)) {
                echo "<div class='success-message'>Mekanik berhasil diperbarui</div>";
                echo "<script>window.location.href = 'mekanik.php';</script>"; // Redirect setelah update berhasil
            } else {
                echo "<div class='error-message'>Error: " . mysqli_error($conn) . "</div>";
            }
        }
    }
}

// Proses hapus mekanik
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Periksa apakah mekanik sedang ditugaskan dalam servis
    $checkQuery = "SELECT COUNT(*) as count FROM servis WHERE id_mekanik = ?";
    $checkStmt = mysqli_prepare($conn, $checkQuery);
    mysqli_stmt_bind_param($checkStmt, "i", $id);
    mysqli_stmt_execute($checkStmt);
    $checkResult = mysqli_stmt_get_result($checkStmt);
    $checkData = mysqli_fetch_assoc($checkResult);
    
    if ($checkData['count'] > 0) {
        echo "<div class='error-message'>Mekanik tidak dapat dihapus karena sedang ditugaskan dalam servis</div>";
    } else {
        $query = "DELETE FROM mekanik WHERE id_mekanik = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $id);
        
        if (mysqli_stmt_execute($stmt)) {
            echo "<div class='success-message'>Mekanik berhasil dihapus</div>";
        } else {
            echo "<div class='error-message'>Error: " . mysqli_error($conn) . "</div>";
        }
    }
}

// Ambil data mekanik untuk ditampilkan
$query = "SELECT * FROM mekanik ORDER BY nama";
$result = mysqli_query($conn, $query);
?>

<div class="content-container">
    <h2>Manajemen Mekanik</h2>

    <!-- Form Tambah Mekanik -->
    <div class="form-container">
        <h3>Tambah Mekanik Baru</h3>
        <form method="post" action="">
            <input type="hidden" name="action" value="add">

            <div class="form-group">
                <label for="nama">Nama Mekanik:</label>
                <input type="text" id="nama" name="nama" required>
            </div>

            <div class="form-group">
                <label for="no_telepon">No. Telepon:</label>
                <input type="text" id="no_telepon" name="no_telepon" required>
            </div>

            <div class="form-group">
                <label for="alamat">Alamat:</label>
                <textarea id="alamat" name="alamat" rows="3" required></textarea>
            </div>

            <div class="form-group">
                <label for="spesialisasi">Spesialisasi:</label>
                <input type="text" id="spesialisasi" name="spesialisasi" required>
            </div>

            <button type="submit" class="btn btn-primary">Tambah Mekanik</button>
        </form>
    </div>

    <!-- Tabel Daftar Mekanik -->
    <div class="table-container">
        <h3>Daftar Mekanik</h3>

        <div style="margin-bottom: 1rem;">
            <input type="text" id="searchInput" onkeyup="searchTable()" placeholder="Cari mekanik..."
                style="padding: 0.5rem; width: 100%; max-width: 300px;">
        </div>

        <table id="dataTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama</th>
                    <th>No. Telepon</th>
                    <th>Alamat</th>
                    <th>Spesialisasi</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo "<tr>";
                        echo "<td>" . $row['id_mekanik'] . "</td>";
                        echo "<td>" . htmlspecialchars($row['nama']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['no_telepon']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['alamat']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['spesialisasi']) . "</td>";
                        echo "<td>
                                <a href='?edit=" . $row['id_mekanik'] . "' class='btn btn-edit'>Edit</a>
                                <a href='?delete=" . $row['id_mekanik'] . "' class='btn btn-delete' onclick='return confirmDelete(\"Yakin ingin menghapus mekanik ini?\")'>Hapus</a>
                              </td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='6'>Tidak ada data mekanik</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- Form Edit Mekanik -->
    <?php
    if (isset($_GET['edit'])) {
        $id = $_GET['edit'];
        $editQuery = "SELECT * FROM mekanik WHERE id_mekanik = ?";
        $editStmt = mysqli_prepare($conn, $editQuery);
        mysqli_stmt_bind_param($editStmt, "i", $id);
        mysqli_stmt_execute($editStmt);
        $editResult = mysqli_stmt_get_result($editStmt);
        
        if ($editData = mysqli_fetch_assoc($editResult)) {
    ?>
    <div class="form-container">
        <h3>Edit Mekanik</h3>
        <form method="post" action="">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id_mekanik" value="<?php echo $editData['id_mekanik']; ?>">

            <div class="form-group">
                <label for="edit_nama">Nama Mekanik:</label>
                <input type="text" id="edit_nama" name="nama" value="<?php echo htmlspecialchars($editData['nama']); ?>"
                    required>
            </div>

            <div class="form-group">
                <label for="edit_no_telepon">No. Telepon:</label>
                <input type="text" id="edit_no_telepon" name="no_telepon"
                    value="<?php echo htmlspecialchars($editData['no_telepon']); ?>" required>
            </div>

            <div class="form-group">
                <label for="edit_alamat">Alamat:</label>
                <textarea id="edit_alamat" name="alamat" rows="3"
                    required><?php echo htmlspecialchars($editData['alamat']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="edit_spesialisasi">Spesialisasi:</label>
                <input type="text" id="edit_spesialisasi" name="spesialisasi"
                    value="<?php echo htmlspecialchars($editData['spesialisasi']); ?>" required>
            </div>

            <div class="action-buttons">
                <button type="submit" class="btn btn-primary">Update Mekanik</button>
                <a href="mekanik.php" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
    <?php
        }
    }
    ?>
</div>

<?php include '../../includes/footer.php'; ?>