<?php

require __DIR__ . "/../../config/cors.php";
header("Content-Type: application/json");

require __DIR__ . "/../../config/koneksi.php";
require __DIR__ . "/../../middleware/auth.php";

// Ambil data JSON
$data = json_decode(file_get_contents("php://input"), true);

// Validasi
if (
    !isset($data['complaintId']) ||
    !isset($data['message']) ||
    !isset($data['status'])
) {
    http_response_code(400);

    echo json_encode([
        "message" => "Data tidak lengkap"
    ]);

    exit;
}

$id = intval($data['complaintId']);
$message = mysqli_real_escape_string($conn, trim($data['message']));
$status = mysqli_real_escape_string($conn, trim($data['status']));

// Cek laporan
$query = mysqli_query($conn, "SELECT * FROM reports WHERE id='$id'");

if (mysqli_num_rows($query) === 0) {
    http_response_code(404);

    echo json_encode([
        "message" => "Laporan tidak ditemukan"
    ]);

    exit;
}

$report = mysqli_fetch_assoc($query);

// Hanya admin
if ($decoded->role !== 'admin') {
    http_response_code(403);

    echo json_encode([
        "message" => "Akses ditolak"
    ]);

    exit;
}

// Update
$update = mysqli_query($conn, "
    UPDATE reports SET
        response = '$message',
        status = '$status'
    WHERE id = '$id'
");

if ($update) {
    echo json_encode([
        "message" => "Laporan berhasil diupdate"
    ]);
} else {
    http_response_code(500);

    echo json_encode([
        "message" => "Gagal update laporan"
    ]);
}