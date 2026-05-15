<?php
require __DIR__ . "/../../config/cors.php";
header("Content-Type: application/json");

require __DIR__ . "/../../config/koneksi.php";

// 1. Cek Method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        "success" => false,
        "message" => "Method tidak diizinkan"
    ]);
    exit;
}

// 2. Ambil data & Sanitasi dasar
$user_id = $_POST['user_id'];
$tanggalLaporan  = $_POST['tanggalLaporan'] ?? '';
$judulLaporan    = $_POST['judulLaporan'] ?? '';
$deskripsi       = $_POST['deskripsi'] ?? '';
$kategoriLaporan = $_POST['kategoriLaporan'] ?? '';
$isiAlamat       = $_POST['isiAlamat'] ?? '';
$latitude        = $_POST['latitude'] ?? null;
$longitude       = $_POST['longitude'] ?? null;
$namaGambar      = '';

// 3. Penanganan Gambar
if (isset($_FILES['gambarLaporan']) && $_FILES['gambarLaporan']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['gambarLaporan'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];

    if (!in_array($ext, $allowed)) {
        echo json_encode(["success" => false, "message" => "Format gambar tidak didukung"]);
        exit;
    }

    $namaGambar = time() . "_" . rand(1000, 9999) . "." . $ext;
    $uploadDir = __DIR__ . "/../../uploads/";
    
    // Buat folder jika belum ada
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $uploadPath = $uploadDir . $namaGambar;

    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        echo json_encode(["success" => false, "message" => "Gagal upload gambar ke server"]);
        exit;
    }
}

// 4. Simpan ke Database menggunakan Prepared Statement (Lebih Aman!)
$sql = "INSERT INTO reports (
            user_id, tanggalLaporan, judulLaporan, deskripsi, 
            kategoriLaporan, isiAlamat, latitude, longitude, gambarLaporan
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {
    // "sssssssss" berarti semua parameter bertipe string. 
    // Sesuaikan jika user_id atau lat/long di DB bertipe Integer/Double
    mysqli_stmt_bind_param($stmt, "sssssssss", 
        $user_id, 
        $tanggalLaporan, 
        $judulLaporan, 
        $deskripsi, 
        $kategoriLaporan, 
        $isiAlamat, 
        $latitude, 
        $longitude, 
        $namaGambar
    );

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode([
            "success" => true,
            "message" => "Laporan berhasil ditambahkan",
            "gambar" => $namaGambar
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Gagal menyimpan ke database: " . mysqli_stmt_error($stmt)
        ]);
    }
    mysqli_stmt_close($stmt);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Gagal menyiapkan query: " . mysqli_error($conn)
    ]);
}