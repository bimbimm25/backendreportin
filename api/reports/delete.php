<?php
ini_set('display_errors', 0); // Matikan display error agar tidak merusak format JSON
require __DIR__ . "/../../config/cors.php";
header("Content-Type: application/json");

require __DIR__ . "/../../config/koneksi.php";

// ========================================================
// 1. VALIDASI TOKEN & AMBIL DATA USER YANG SEDANG LOGIN
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
    
    // Ambil ID User yang sedang menekan tombol hapus dari payload JWT
    $id_dari_jwt = $jwtPayload->id ?? $jwtPayload->user_id ?? $jwtPayload->id_admin ?? null;

    if (!$id_dari_jwt) {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Token valid, tetapi ID user tidak ditemukan."]);
        exit;
    }

    $user_id = mysqli_real_escape_string($conn, $id_dari_jwt); 

} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Sesi masuk kedaluwarsa atau tidak valid."]);
    exit;
}

// ========================================================
// 2. PROSES VALIDASI KEPEMILIKAN LAPORAN (PROTEKSI USER)
// ========================================================

// Tangkap data JSON dari React kamu
$data = json_decode(file_get_contents("php://input"), true);
$id_laporan = isset($data['id']) ? mysqli_real_escape_string($conn, $data['id']) : null;

if (!$id_laporan) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "ID Laporan tidak ditemukan atau kosong."]);
    exit;
}

// 🕵️‍♂️ Cek dulu ke database, apakah laporan ini benar-benar milik user_id yang sedang login?
$checkOwnerQuery = mysqli_query($conn, "SELECT user_id FROM reports WHERE id='$id_laporan' LIMIT 1");
$laporan = mysqli_fetch_assoc($checkOwnerQuery);

if (!$laporan) {
    http_response_code(404);
    echo json_encode(["status" => "error", "message" => "Laporan tidak ditemukan di database."]);
    exit;
}

// 🛑 JIKA user_id di laporan TIDAK SAMA dengan user_id di token JWT, TOLAK! (Termasuk jika diklik oleh Admin)
if ($laporan['user_id'] !== $user_id) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Akses ditolak! Kamu hanya boleh menghapus laporan milikmu sendiri."]);
    exit;
}

// ========================================================
// 3. JIKA LOLOS VALIDASI, JALANKAN EKSEKUSI PENGHAPUSAN
// ========================================================
$deleteQuery = mysqli_query($conn, "DELETE FROM reports WHERE id='$id_laporan'");

if ($deleteQuery) {
    http_response_code(200);
    echo json_encode(["status" => "success", "message" => "Laporan berhasil dihapus"]);
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Gagal menghapus data dari database: " . mysqli_error($conn)]);
}
exit;
?>