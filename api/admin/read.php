<?php

require __DIR__ . "/../../config/cors.php";
header("Content-Type: application/json");

require __DIR__ . "/../../config/koneksi.php";
require __DIR__ . "/../../middleware/admin.php";

$query = mysqli_query($conn, "
    SELECT id, username, email, role
    FROM users
    WHERE role = 'admin'
    ORDER BY id DESC
");

$data = [];

while($row = mysqli_fetch_assoc($query)) {
    $data[] = [
        "id" => $row['id'],
        "name" => $row['username'],
        "email" => $row['email'],
        "role" => $row['role']
    ];
}

echo json_encode($data);