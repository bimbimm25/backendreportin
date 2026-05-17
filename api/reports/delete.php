<?php
ini_set('display_errors', 0); 
require __DIR__ . "/../../config/cors.php";
header("Content-Type: application/json");

require __DIR__ . "/../../config/koneksi.php";

// ========================================================
// 1. VALIDASI TOKEN (Mencegah Stuck & Mengatasi Blokir Cloud)
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
    echo json_encode(["status" => "error", "message" => "Akses ditolak, token tidak ditemukan."]);
    exit;
}

$arr = explode(" ", $authHeader);
$token = isset($arr[1]) ? $arr[1] : '';

try {
    $tokenParts = explode('.', $token);
    if (count($tokenParts) < 2) {
        throw new Exception("Token tidak valid.");
    }
    $tokenPayload = base64_decode($tokenParts[1]);
    $jwtPayload = json_decode($tokenPayload);
    
    // Ambil data role untuk memastikan hanya ADMIN yang bisa menghapus
    $role = isset($jwtPayload->role) ? strtolower($jwtPayload->role) : 'user';
    $username_jwt = isset($jwtPayload->username) ? strtolower($jwtPayload->username) : '';
    $is_admin = ($role === 'admin' || $role === 'superadmin' || strpos($username_jwt, 'admin') !== false);

    if (!$is_admin) {
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "Akses ditolak, hanya Admin yang boleh menghapus."]);
        exit;
    }

} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Sesi masuk kedaluwarsa atau tidak valid."]);
    exit;
}

// ========================================================
// 2. PROSES EKSEKUSI PENGHAPUSAN DATA
// ========================================================

// Tangkap data JSON dari React
$data = json_decode(file_get_contents("php://input"), true);
$id = isset($data['id']) ? mysqli_real_escape_string($conn, $data['id']) : null;

if (!$id) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "ID Laporan tidak ditemukan atau kosong."]);
    exit;
}

// Jalankan query delete
$deleteQuery = mysqli_query($conn, "DELETE FROM reports WHERE id='$id'");

if ($deleteQuery) {
    http_response_code(200);
    echo json_encode(["status" => "success", "message" => "Laporan berhasil dihapus"]);
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Gagal menghapus data dari database: " . mysqli_error($conn)]);
}
exit;
?>