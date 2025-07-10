<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: /bengkel/login.php");
        exit;
    }
}

function requireRole($role) {
    requireLogin();
    if ($_SESSION['role'] != $role && $_SESSION['role'] != 'admin') {
        header("Location: /bengkel/index.php?error=unauthorized");
        exit;
    }
}

function getUserRole() {
    return $_SESSION['role'] ?? '';
}

function redirectBasedOnRole() {
    $role = getUserRole();
    
    switch ($role) {
        case 'admin':
            header("Location: /bengkel/pages/admin/dashboard.php");
            break;
        case 'kasir':
            header("Location: /bengkel/pages/kasir/dashboard.php");
            break;
        case 'user':
            header("Location: /bengkel/pages/user/dashboard.php");
            break;
        default:
            header("Location: /bengkel/login.php");
    }
    exit;
}
?>