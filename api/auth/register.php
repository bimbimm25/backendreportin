<?php
require __DIR__ . "/../../config/cors.php";
header("Content-Type: application/json");
require __DIR__ . "/../../config/koneksi.php";


$data = json_decode(file_get_contents("php://input"), true);

$username = $data['username'];
$email = $data['email'];
$password = password_hash($data['password'], PASSWORD_BCRYPT);

// cek email
$cek = mysqli_query($conn, "SELECT id FROM users WHERE email='$email'");
if (mysqli_num_rows($cek) > 0) {
    echo json_encode(["message" => "Email sudah digunakan"]);
    exit;
}

// insert user
mysqli_query($conn, "
    INSERT INTO users (username, email, password, role)
    VALUES ('$username', '$email', '$password', 'user')
");

echo json_encode(["message" => "Register berhasil"]);