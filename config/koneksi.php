<?php
// Ambil data dari file .env yang sudah kamu buat
$host = $_ENV['DB_HOST'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'];
$db   = $_ENV['DB_NAME'];
$port = $_ENV['DB_PORT']; // Parameter baru untuk port Railway

// Masukkan $port sebagai parameter ke-5
$conn = mysqli_connect($host, $user, $pass, $db, $port);

if (!$conn) {
    die("Koneksi ke Railway gagal: " . mysqli_connect_error());
}
?>