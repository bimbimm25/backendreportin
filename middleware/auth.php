<?php
require __DIR__ . "/../vendor/autoload.php";

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$key = "RAHASIA_SUPER_SECRET_KEY_2026_VERY_LONG_32_CHARS_MINIMUM";

$headers = getallheaders();

if (!isset($headers['Authorization'])) {
    echo json_encode(["message" => "Token tidak ada"]);
    exit;
}

$token = str_replace("Bearer ", "", $headers['Authorization']);

try {
    $decoded = JWT::decode($token, new Key($key, 'HS256'));
} catch (Exception $e) {
    echo json_encode(["message" => "Token tidak valid"]);
    exit;
}