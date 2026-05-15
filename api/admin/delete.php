<?php
// 1. Header & Security
require __DIR__ . "/../../config/cors.php";
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: DELETE");

// 2. Koneksi & Middleware
require __DIR__ . "/../../config/koneksi.php";
require __DIR__ . "/../../middleware/admin.php"; 

// 3. Ambil ID dari URL (Contoh: delete.php?id=5)
$id_to_delete = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$id_to_delete) {
    echo json_encode(["success" => false, "message" => "ID Admin tidak ditemukan"]);
    exit;
}

// Pencegahan: Jangan biarkan admin menghapus dirinya sendiri (Opsional tapi disarankan)
if ($id_to_delete == $decoded->id) {
    echo json_encode(["success" => false, "message" => "Anda tidak bisa menghapus akun Anda sendiri"]);
    exit;
}

// 4. Cari tahu nama admin yang akan dihapus (untuk keperluan log)
$get_admin = mysqli_query($conn, "SELECT username FROM users WHERE id = $id_to_delete AND role = 'admin'");
$admin_data = mysqli_fetch_assoc($get_admin);

if (!$admin_data) {
    echo json_encode(["success" => false, "message" => "Admin tidak ditemukan"]);
    exit;
}

$target_name = $admin_data['username'];

// 5. Mulai Transaksi
mysqli_begin_transaction($conn);

try {
    // A. Hapus Admin
    $queryDelete = mysqli_query($conn, "DELETE FROM users WHERE id = $id_to_delete");

    // B. Catat ke Log Aktivitas
    $admin_pelaksana = $decoded->username; // Nama admin yang sedang login
    $admin_id_pelaksana = $decoded->id;
    $pesanLog = "Menghapus admin: $target_name";

    $queryLog = mysqli_query($conn, "
        INSERT INTO logs (admin_id, adminName, pesan, tanggal) 
        VALUES ($admin_id_pelaksana, '$admin_pelaksana', '$pesanLog', NOW())
    ");

    mysqli_commit($conn);

    echo json_encode([
        "success" => true, 
        "message" => "Admin $target_name berhasil dihapus dan tercatat di log"
    ]);

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode([
        "success" => false, 
        "message" => "Gagal menghapus admin",
        "error" => $e->getMessage()
    ]);
}