<?php

require __DIR__ . "/../../config/cors.php";
header("Content-Type: application/json");

require __DIR__ . "/../../config/koneksi.php";
require __DIR__ . "/../../middleware/admin.php";

if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        "success" => false,
        "message" => "Method tidak diizinkan"
    ]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$name = $data['name'] ?? '';
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

if(!$name || !$email || !$password) {
    echo json_encode([
        "success" => false,
        "message" => "Semua field wajib diisi"
    ]);
    exit;
}

$cek = mysqli_query($conn, "
    SELECT * FROM users WHERE email='$email'
");

if(mysqli_num_rows($cek) > 0) {
    echo json_encode([
        "success" => false,
        "message" => "Email sudah digunakan"
    ]);
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$query = mysqli_query($conn, "
    INSERT INTO users(username,email,password,role)
    VALUES(
        '$name',
        '$email',
        '$hash',
        'admin'
    )
");

if($query) {
    // Ambil nama admin yang sedang login dari middleware
    $current_admin_name = $decoded->username ?? 'System'; 
    
    // Pesan log
    $pesanLog = "Menambahkan admin baru: $name";

    // Simpan ke tabel logs
    $stmtLog = $conn->prepare("INSERT INTO logs (admin_id, adminName, pesan, tanggal) VALUES (?, ?, ?, CURRENT_TIMESTAMP)");
    $stmtLog->bind_param("iss", $decoded->id, $current_admin_name, $pesanLog);
    $stmtLog->execute();

    echo json_encode(["success" => true, "message" => "Admin berhasil ditambahkan"]);
} else {
    echo json_encode(["success" => false, "message" => "Gagal menambahkan admin"]);
}