<?php
require __DIR__ . "/../vendor/autoload.php";

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$key = "RAHASIA_SUPER_SECRET_KEY_2026_VERY_LONG_32_CHARS_MINIMUM";

$headers = getallheaders();

// cek token ada
if (!isset($headers['Authorization'])) {
    echo json_encode(["message" => "Akses ditolak, token tidak ada"]);
    exit;
}

$token = str_replace("Bearer ", "", $headers['Authorization']);

try {
    $decoded = JWT::decode($token, new Key($key, 'HS256'));

    // 🔥 CEK ADMIN DI SINI
    if ($decoded->role !== 'admin') {
        echo json_encode(["message" => "Akses ditolak, khusus admin"]);
        exit;
    }

} catch (Exception $e) {
    echo json_encode(["message" => "Token tidak valid"]);
    exit;
}