<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require __DIR__ . "/../../config/cors.php";
header("Content-Type: application/json");

require __DIR__ . "/../../config/koneksi.php";

// ========================================================
// PROSES PEMBONGKARAN JWT TOKEN (UNTUK MENDAPATKAN USER ID)
// ========================================================
$authHeader = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';

if (!$authHeader) {
    http_response_code(401);
    echo json_encode(["message" => "Akses ditolak, token tidak ditemukan."]);
    exit;
}

// Pecah string "Bearer <token>" untuk mengambil tokennya saja
$arr = explode(" ", $authHeader);
$token = isset($arr[1]) ? $arr[1] : '';

if (!$token) {
    http_response_code(401);
    echo json_encode(["message" => "Format token salah."]);
    exit;
}

try {
    // Bongkar payload JWT secara manual (sangat aman & praktis untuk native PHP tanpa library tambahan)
    $tokenParts = explode('.', $token);
    if (count($tokenParts) < 2) {
        throw new Exception("Token tidak valid.");
    }
    $tokenPayload = base64_decode($tokenParts[1]);
    $jwtPayload = json_decode($tokenPayload);
    

    $user_id = mysqli_real_escape_string($conn, $jwtPayload->id); 

} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["message" => "Sesi masuk tidak valid atau kedaluwarsa."]);
    exit;
}


// ========================================================
// DETAIL BERDASARKAN ID (Tetap aman & bisa diakses)
// ========================================================
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


// ========================================================
// AMBIL DATA LAPORAN MILIK USER SENDIRI (KERANJANG PRIVAT)
// ========================================================

$query = mysqli_query($conn, "
    SELECT reports.*, users.username
    FROM reports
    LEFT JOIN users ON users.id = reports.user_id
    WHERE reports.user_id = '$user_id'
    ORDER BY reports.created_at DESC
");

$data = [];

while ($row = mysqli_fetch_assoc($query)) {
    $data[] = $row;
}

// Kirimkan data laporan yang sudah terfilter ke React kamu
echo json_encode($data);