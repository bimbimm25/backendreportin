<?php
require __DIR__ . "/../../config/cors.php";
header("Content-Type: application/json");

require __DIR__ . "/../../config/koneksi.php";
require __DIR__ . "/../../middleware/admin.php";


$data = json_decode(file_get_contents("php://input"), true);

$id = $data['id'];

mysqli_query($conn, "DELETE FROM reports WHERE id='$id'");

echo json_encode(["message" => "Laporan berhasil dihapus"]);