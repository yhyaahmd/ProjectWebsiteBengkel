<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

requireRole('admin');

$pageTitle = "Manajemen Pelanggan";
include '../../includes/header.php';

// Proses form tambah/edit pelanggan
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        // Tambah pelanggan baru
        if ($_POST['action'] == 'add') {
            $nama = $_POST['nama'];
            $no_telepon = $_POST['no_telepon'];
            $alamat = $_POST['alamat'];
            $email = $_POST['email'];
            
            $query = "INSERT INTO pelanggan (nama, no_telepon, alamat, email) 
                      VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ssss", $nama, $no_telepon, $alamat, $email);
            
            if (mysqli_stmt_execute($stmt)) {
                // Jika pelanggan berhasil ditambahkan, buat juga user account
                $id_pelanggan = mysqli_insert_id($conn);
                $username = strtolower(str_replace(' ', '', $nama)) . rand(100, 999);
                $password = substr(md5(rand()), 0, 8); // Generate random password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $userQuery = "INSERT INTO users (username, password, nama, role, id_pelanggan) 
                             VALUES (?, ?, ?, 'user', ?)";
                $userStmt = mysqli_prepare($conn, $userQuery);
                mysqli_stmt_bind_param($userStmt, "sssi", $username, $hashed_password, $nama, $id_pelanggan);
                
                if (mysqli_stmt_execute($userStmt)) {
                    echo "<div class='success-message'>
                            Pelanggan berhasil ditambahkan<br>
                            Username: " . $username . "<br>
                            Password: " . $password . "<br>
                            <strong>Harap catat informasi login ini!</strong>
                          </div>";
                } else {
                    echo "<div class='error-message'>Error membuat user account: " . mysqli_error($conn) . "</div>";
                }
            } else {
                echo "<div class='error-message'>Error: " . mysqli_error($conn) . "</div>";
            }
        }
        
        // Edit pelanggan
        if ($_POST['action'] == 'edit') {
            $id = $_POST['id_pelanggan'];
            $nama = $_POST['nama'];
            $no_telepon = $_POST['no_telepon'];
            $alamat = $_POST['alamat'];
            $email = $_POST['email'];
            
            $query = "UPDATE pelanggan SET 
                      nama = ?, 
                      no_telepon = ?, 
                      alamat = ?, 
                      email = ? 
                      WHERE id_pelanggan = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ssssi", $nama, $no_telepon, $alamat, $email, $id);
            
            if (mysqli_stmt_execute($stmt)) {
                // Update juga nama di tabel users jika ada
                $updateUserQuery = "UPDATE users SET nama = ? WHERE id_pelanggan = ?";
                $updateUserStmt = mysqli_prepare($conn, $updateUserQuery);
                if ($updateUserStmt) {
                    mysqli_stmt_bind_param($updateUserStmt, "si", $nama, $id);
                    mysqli_stmt_execute($updateUserStmt);
                }
                
                echo "<div class='success-message'>Pelanggan berhasil diperbarui</div>";
                echo "<script>window.location.href = 'pelanggan.php';</script>"; // Redirect setelah update berhasil
            } else {
                echo "<div class='error-message'>Error: " . mysqli_error($conn) . "</div>";
            }
        }
    }
}

// Proses hapus pelanggan
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Periksa apakah pelanggan memiliki servis
    $checkQuery = "SELECT COUNT(*) as count FROM servis WHERE id_pelanggan = ?";
    $checkStmt = mysqli_prepare($conn, $checkQuery);
    mysqli_stmt_bind_param($checkStmt, "i", $id);
    mysqli_stmt_execute($checkStmt);
    $checkResult = mysqli_stmt_get_result($checkStmt);
    $checkData = mysqli_fetch_assoc($checkResult);
    
    if ($checkData['count'] > 0) {
        echo "<div class='error-message'>Pelanggan tidak dapat dihapus karena memiliki riwayat servis</div>";
    } else {
        // Hapus user account terkait jika ada
        $deleteUserQuery = "DELETE FROM users WHERE id_pelanggan = ?";
        $deleteUserStmt = mysqli_prepare($conn, $deleteUserQuery);
        mysqli_stmt_bind_param($deleteUserStmt, "i", $id);
        mysqli_stmt_execute($deleteUserStmt);
        
        // Hapus pelanggan
        $query = "DELETE FROM pelanggan WHERE id_pelanggan = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $id);
        
        if (mysqli_stmt_execute($stmt)) {
            echo "<div class='success-message'>Pelanggan berhasil dihapus</div>";
        } else {
            echo "<div class='error-message'>Error: " . mysqli_error($conn) . "</div>";
        }
    }
}

// Ambil data pelanggan untuk ditampilkan
$query = "SELECT * FROM pelanggan ORDER BY nama";
$result = mysqli_query($conn, $query);
?>

<div class="content-container">
    <h2>Manajemen Pelanggan</h2>

    <!-- Form Tambah Pelanggan -->
    <div class="form-container">
        <h3>Tambah Pelanggan Baru</h3>
        <form method="post" action="">
            <input type="hidden" name="action" value="add">

            <div class="form-group">
                <label for="nama">Nama Pelanggan:</label>
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
                <label for="email">Email:</label>
                <input type="email" id="email" name="email">
            </div>

            <button type="submit" class="btn btn-primary">Tambah Pelanggan</button>
        </form>
    </div>

    <!-- Tabel Daftar Pelanggan -->
    <div class="table-container">
        <h3>Daftar Pelanggan</h3>

        <div style="margin-bottom: 1rem;">
            <input type="text" id="searchInput" onkeyup="searchTable()" placeholder="Cari pelanggan..."
                style="padding: 0.5rem; width: 100%; max-width: 300px;">
        </div>

        <table id="dataTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama</th>
                    <th>No. Telepon</th>
                    <th>Alamat</th>
                    <th>Email</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo "<tr>";
                        echo "<td>" . $row['id_pelanggan'] . "</td>";
                        echo "<td>" . htmlspecialchars($row['nama']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['no_telepon']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['alamat']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                        echo "<td>
                                <a href='?edit=" . $row['id_pelanggan'] . "' class='btn btn-edit'>Edit</a>
                                <a href='?delete=" . $row['id_pelanggan'] . "' class='btn btn-delete' onclick='return confirmDelete(\"Yakin ingin menghapus pelanggan ini?\")'>Hapus</a>
                              </td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='6'>Tidak ada data pelanggan</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- Form Edit Pelanggan -->
    <?php
    if (isset($_GET['edit'])) {
        $id = $_GET['edit'];
        $editQuery = "SELECT * FROM pelanggan WHERE id_pelanggan = ?";
        $editStmt = mysqli_prepare($conn, $editQuery);
        mysqli_stmt_bind_param($editStmt, "i", $id);
        mysqli_stmt_execute($editStmt);
        $editResult = mysqli_stmt_get_result($editStmt);
        
        if ($editData = mysqli_fetch_assoc($editResult)) {
    ?>
    <div class="form-container">
        <h3>Edit Pelanggan</h3>
        <form method="post" action="">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id_pelanggan" value="<?php echo $editData['id_pelanggan']; ?>">

            <div class="form-group">
                <label for="edit_nama">Nama Pelanggan:</label>
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
                <label for="edit_email">Email:</label>
                <input type="email" id="edit_email" name="email"
                    value="<?php echo htmlspecialchars($editData['email']); ?>">
            </div>

            <div class="action-buttons">
                <button type="submit" class="btn btn-primary">Update Pelanggan</button>
                <a href="pelanggan.php" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
    <?php
        }
    }
    ?>
</div>

<?php include '../../includes/footer.php'; ?>