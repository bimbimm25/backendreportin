<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require __DIR__ . "/../../config/cors.php";
header("Content-Type: application/json");

require __DIR__ . "/../../config/koneksi.php";

// ========================================================
// 1. PROSES PEMBONGKARAN JWT TOKEN (ANTI BLOKIR CLOUD)
// ========================================================
$authHeader = '';
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
} elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
    $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
} elseif (function_exists('apache_request_headers')) {
    $headers = apache_request_headers();
    if (isset($headers['Authorization'])) {
        $authHeader = $headers['Authorization'];
    }
}

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
    // Bongkar payload JWT secara manual
    $tokenParts = explode('.', $token);
    if (count($tokenParts) < 2) {
        throw new Exception("Token tidak valid.");
    }
    $tokenPayload = base64_decode($tokenParts[1]);
    $jwtPayload = json_decode($tokenPayload);
    
    // Ambil ID User dari payload JWT
    $id_dari_jwt = $jwtPayload->id ?? $jwtPayload->user_id ?? $jwtPayload->id_admin ?? null;

    if (!$id_dari_jwt) {
        http_response_code(401);
        echo json_encode(["message" => "Token valid, tetapi properti ID tidak ditemukan di dalam payload."]);
        exit;
    }

    $user_id = mysqli_real_escape_string($conn, $id_dari_jwt); 

} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["message" => "Sesi masuk tidak valid atau kedaluwarsa."]);
    exit;
}


// ========================================================
// 2. DETAIL BERDASARKAN ID LAPORAN (Untuk Fitur Detail/Lihat)
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
// 3. AMBIL DATA LAPORAN (VERSI PINTAR: USER VS ADMIN)
// ========================================================

// Deteksi role dari JWT (antisipasi huruf besar/kecil)
$role = isset($jwtPayload->role) ? strtolower($jwtPayload->role) : 'user'; 

// Backup: jika username di JWT mengandung kata 'admin', anggap dia admin
$username_jwt = isset($jwtPayload->username) ? strtolower($jwtPayload->username) : '';
$is_admin_backup = (strpos($username_jwt, 'admin') !== false);

// 📊 KONDISI A: Jika yang login ADMIN / SUPERADMIN
if ($role === 'admin' || $role === 'superadmin' || $is_admin_backup) {
    $query = mysqli_query($conn, "
        SELECT reports.*, users.username
        FROM reports
        LEFT JOIN users ON users.id = reports.user_id
        ORDER BY reports.created_at DESC
    ");
} 
// 📊 KONDISI B: Jika yang login USER BIASA (Filter berdasarkan user_id masing-masing)
else {
    $query = mysqli_query($conn, "
        SELECT reports.*, users.username
        FROM reports
        LEFT JOIN users ON users.id = reports.user_id
        WHERE reports.user_id = '$user_id'
        ORDER BY reports.created_at DESC
    ");
}

// Jalur penyelamat jika query di atas bermasalah (agar data tetap keluar)
if (!$query) {
    $query = mysqli_query($conn, "
        SELECT reports.*, users.username
        FROM reports
        LEFT JOIN users ON users.id = reports.user_id
        ORDER BY reports.created_at DESC
    ");
}

$data = [];

while ($row = mysqli_fetch_assoc($query)) {
    $data[] = $row;
}

// Kirimkan data laporan ke React kamu
echo json_encode($data);
?>