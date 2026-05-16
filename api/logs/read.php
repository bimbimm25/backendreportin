<?php
// 1. Header & Security
require __DIR__ . "/../../config/cors.php";
header("Content-Type: application/json");

// 2. Koneksi & Middleware
require __DIR__ . "/../../config/koneksi.php";
require __DIR__ . "/../../middleware/admin.php"; 

// 3. Pengaturan Limit
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;


$sql = "
    SELECT 
        logs.adminName AS directAdminName, 
        logs.pesan, 
        logs.tanggal, 
        users.username AS joinedUsername
    FROM logs
    LEFT JOIN users ON users.id = logs.admin_id
    ORDER BY logs.id DESC
    LIMIT $limit
";

$query = mysqli_query($conn, $sql);

$data = [];

if ($query) {
    while($row = mysqli_fetch_assoc($query)) {
        
        $finalName = $row['directAdminName'];
        
        if (empty($finalName)) {
            $finalName = $row['joinedUsername'] ?? 'Admin/System';
        }

        $data[] = [
            "adminName" => $finalName,
            "message"   => $row['pesan'],
            "tanggal"   => $row['tanggal']
        ];
    }
}

// 5. Output JSON
echo json_encode($data);