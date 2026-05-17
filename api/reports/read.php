<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require __DIR__ . "/../../config/cors.php";
header("Content-Type: application/json");

require __DIR__ . "/../../config/koneksi.php";

// =========================
// DETAIL BERDASARKAN ID
// =========================
if (isset($_GET['id'])) {

    $id = mysqli_real_escape_string($conn, $_GET['id']);

    $query = mysqli_query($conn, "
        SELECT reports.*, users.username
        FROM reports
        LEFT JOIN users ON users.id = reports.user_id
        WHERE reports.id = '$id'
        LIMIT 1
    ");

    $data = mysqli_fetch_assoc($query);

    echo json_encode($data);
    exit;
}

// =========================
// AMBIL SEMUA DATA
// =========================
$query = mysqli_query($conn, "
    SELECT reports.*, users.username
    FROM reports
    LEFT JOIN users ON users.id = reports.user_id
    ORDER BY reports.created_at DESC
");

$data = [];

while ($row = mysqli_fetch_assoc($query)) {
    $data[] = $row;
}

echo json_encode($data);