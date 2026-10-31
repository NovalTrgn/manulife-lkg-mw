<?php
require_once 'config/database.php';
include_once 'includes/header.php';

$page = $_GET['page'] ?? 'dashboard';

switch ($page) {
    case 'presensi':
        include 'pages/presensi.php';
        break;
    case 'activity':
        include 'pages/activity.php';
        break;
    case 'rekrut':
        include 'pages/rekrut.php';
        break;
    case 'kelola_agen':
        if ($_SESSION['role'] === 'Admin') {
            include 'pages/kelola_agen.php';
        } else {
            echo "<div class='alert alert-danger'>Akses Khusus Admin.</div>";
        }
        break;
    case 'settings':
        if ($_SESSION['role'] === 'Admin') {
            include 'pages/settings.php';
        } else {
            echo "<div class='alert alert-danger'>Akses Khusus Admin.</div>";
        }
        break;
    case 'profile':
        include 'pages/profile.php';
        break;
    case 'dashboard':
    default:
        include 'pages/dashboard.php';
        break;
}

include_once 'includes/footer.php';
?>