<?php
require __DIR__ . "/../../config/cors.php";
header("Content-Type: application/json");

require __DIR__ . "/../../config/koneksi.php";
require __DIR__ . "/../../vendor/autoload.php";


use Firebase\JWT\JWT;
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/../../");
$dotenv->safeLoad();

// Coba ambil dari Railway dulu
$SECRET_KEY_JWT = getenv('SECRET_KEY_JWT');

// Jika kosong (artinya kamu sedang run di localhost pakai XAMPP), ambil dari Dotenv
if (!$SECRET_KEY_JWT) {
    $SECRET_KEY_JWT = $_ENV['SECRET_KEY_JWT'] ?? null;
}

// Ambil input JSON
$inputJSON = file_get_contents("php://input");
$data = json_decode($inputJSON, true);

// Validasi apakah JSON valid dan memiliki field yang diperlukan
if (!$data || !isset($data['email']) || !isset($data['password'])) {
    http_response_code(400); // Bad Request
    echo json_encode(["message" => "Email dan password harus diisi dalam format JSON"]);
    exit;
}

$email = trim($data['email']);
$password = $data['password'];

// Validasi tidak boleh kosong
if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(["message" => "Email dan password tidak boleh kosong"]);
    exit;
}

// Gunakan prepared statement untuk mencegah SQL Injection
$query = "SELECT * FROM users WHERE email = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Validasi kredensial
if (!$user || !password_verify($password, $user['password'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(["message" => "Email atau password salah"]);
    exit;
}

// Buat payload JWT
$payload = [
    "id"    => $user['id'],
    "username" => $user['username'],
    "email" => $user['email'],
    "role"  => $user['role'],
    "exp"   => time() + (60 * 60 * 24)
];

$token = JWT::encode($payload, $SECRET_KEY_JWT, 'HS256');

// Response sukses
echo json_encode([
    "message" => "Login berhasil",
    "token"   => $token,
    "role"    => $user['role'],
    "user"    => [
        "id"       => $user['id'],
        "username" => $user['username'],
        "email"    => $user['email']
    ]
]);