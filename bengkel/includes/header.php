<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

if (!isset($pageTitle)) {
    $pageTitle = "Sistem Manajemen Bengkel";
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Bengkel</title>
    <link rel="stylesheet" href="/bengkel/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = {
        important: true,
        corePlugins: {
            preflight: false,
        }
    }
    </script>
</head>

<body>
    <header>
        <div class="logo">
            <h1>Bengkel Pro</h1>
        </div>
        <nav>
            <?php if (isLoggedIn()): ?>
            <?php if (getUserRole() == 'admin'): ?>
            <a href="/bengkel/pages/admin/dashboard.php">Dashboard</a>
            <a href="/bengkel/pages/admin/pelanggan.php">Pelanggan</a>
            <a href="/bengkel/pages/admin/mekanik.php">Mekanik</a>
            <a href="/bengkel/pages/admin/sparepart.php">Sparepart</a>
            <a href="/bengkel/pages/admin/laporan.php">Laporan</a>
            <?php elseif (getUserRole() == 'kasir'): ?>
            <a href="/bengkel/pages/kasir/dashboard.php">Dashboard</a>
            <a href="/bengkel/pages/kasir/servis.php">Servis</a>
            <a href="/bengkel/pages/kasir/transaksi.php">Transaksi</a>
            <?php elseif (getUserRole() == 'user'): ?>
            <a href="/bengkel/pages/user/dashboard.php">Dashboard</a>
            <a href="/bengkel/pages/user/riwayat.php">Riwayat Servis</a>
            <?php endif; ?>
            <a href="/bengkel/logout.php">Logout</a>
            <?php else: ?>
            <a href="/bengkel/login.php">Login</a>
            <?php endif; ?>
        </nav>
    </header>
    <main>