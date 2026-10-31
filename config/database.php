<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "agency_db";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi Database Gagal: " . mysqli_connect_error());
}
?>