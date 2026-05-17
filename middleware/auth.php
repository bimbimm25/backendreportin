<?php
require __DIR__ . "/../vendor/autoload.php";

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . "/..");
$dotenv->safeLoad();

$SECRET_KEY_JWT = getenv('SECRET_KEY_JWT');

// Jika kosong (artinya kamu sedang run di localhost pakai XAMPP), ambil dari Dotenv
if (!$SECRET_KEY_JWT) {
    $SECRET_KEY_JWT = $_ENV['SECRET_KEY_JWT'] ?? null;
}

$headers = getallheaders();

if (!isset($headers['Authorization'])) {
    echo json_encode(["message" => "Token tidak ada"]);
    exit;
}

$token = str_replace("Bearer ", "", $headers['Authorization']);

try {
    $decoded = JWT::decode($token, new Key($SECRET_KEY_JWT, 'HS256'));
} catch (Exception $e) {
    echo json_encode(["message" => "Token tidak valid"]);
    exit;
}