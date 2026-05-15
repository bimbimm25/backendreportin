<?php
require __DIR__ . "/../../config/cors.php";
header("Content-Type: application/json");

require __DIR__ . "/../../config/koneksi.php";


// total laporan
$total = mysqli_query($conn, "SELECT COUNT(*) as total FROM reports");
$total = mysqli_fetch_assoc($total)['total'];   

// pending
$pending = mysqli_query($conn, "SELECT COUNT(*) as total FROM reports WHERE status='pending'");
$pending = mysqli_fetch_assoc($pending)['total'];

// diproses
$diproses = mysqli_query($conn, "SELECT COUNT(*) as total FROM reports WHERE status='diproses'");
$diproses = mysqli_fetch_assoc($diproses)['total'];

// selesai
$selesai = mysqli_query($conn, "SELECT COUNT(*) as total FROM reports WHERE status='selesai'");
$selesai = mysqli_fetch_assoc($selesai)['total'];

echo json_encode([
    "total" => (int)$total,
    "belum_selesai" => (int)$pending,
    "diproses" => (int)$diproses,
    "selesai" => (int)$selesai,
    "total_kritik" => (int)$total,
]);
?>