<?php
// 1. Pengaturan Header & CORS agar React bisa mengaksesnya
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';


$allowed_domains = [
    'http://localhost:5173',
    'https://report-in-fe.vercel.app'
];


if (in_array($origin, $allowed_domains)) {
    header("Access-Control-Allow-Origin: $origin");
}
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: PUT, PATCH, POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Tangani preflight request dari browser (karena pakai Axios)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}


require __DIR__ . "/../../config/koneksi.php";
require __DIR__ . "/../../middleware/admin.php"; 

// 3. Tangkap data JSON dari React
$data = json_decode(file_get_contents("php://input"));

// 4. Validasi Data
if (!empty($data->id_laporan) && !empty($data->tanggapan) && !empty($data->status)) {

    $id = $data->id_laporan;
    $tanggapan = $data->tanggapan;
    $status = $data->status;

    // Ambil info admin dari token (hasil decode middleware)
    $admin_id = $decoded->id;
    $admin_name = $decoded->username;

    // Mulai Transaksi Database (Agar jika salah satu gagal, semua batal)
    $conn->begin_transaction();

    try {
        // 5. Query UPDATE Laporan
        $queryUpdate = "UPDATE reports 
                        SET tanggapan_admin = ?, 
                            tanggal_tanggapan = CURRENT_TIMESTAMP, 
                            status = ? 
                        WHERE id = ?";
        
        $stmtUpdate = $conn->prepare($queryUpdate);
        $stmtUpdate->bind_param("ssi", $tanggapan, $status, $id);
        $stmtUpdate->execute();

        // 6. Query INSERT ke Log Aktivitas
        // Kita buat pesan otomatis untuk log
        if ($stmtUpdate->affected_rows > 0) {
    $admin_id = $decoded->id;
    $admin_name = $decoded->username;

    // Buat pesan log yang informatif
    $pesanLog = "Membalas laporan ID #$id dengan status: $status";
    
    $queryLog = "INSERT INTO logs (admin_id, adminName, pesan, tanggal) VALUES (?, ?, ?, CURRENT_TIMESTAMP)";
    $stmtLog = $conn->prepare($queryLog);
    $stmtLog->bind_param("iss", $admin_id, $admin_name, $pesanLog);
    $stmtLog->execute();

    $conn->commit(); // Simpan permanen kedua perubahan
    echo json_encode(["success" => true, "message" => "Tanggapan terkirim dan dicatat di log."]);
}
    } catch (Exception $e) {
        // Jika ada yang gagal, batalkan semua perubahan
        $conn->rollback();
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Gagal memproses data.",
            "error" => $e->getMessage()
        ]);
    }

    $stmtUpdate->close();
    $stmtLog->close();

} else {
    http_response_code(400);
    echo json_encode(["message" => "Data tidak lengkap."]);
}