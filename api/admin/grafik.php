<?php
require __DIR__ . "/../../config/cors.php";
header("Content-Type: application/json");

require __DIR__ . "/../../config/koneksi.php";



// ambil data minggu ini
$query = mysqli_query($conn, "
    SELECT 
        DAYOFWEEK(tanggalLaporan) as hari,
        COUNT(*) as total
    FROM reports
    WHERE YEAR(tanggalLaporan) = YEAR(CURDATE())
    AND WEEK(tanggalLaporan, 1) = WEEK(CURDATE(), 1)
    GROUP BY DAYOFWEEK(tanggalLaporan)
");

// mapping hari
$mapHari = [
    2 => "Senin",
    3 => "Selasa",
    4 => "Rabu",
    5 => "Kamis",
    6 => "Jumat",
    7 => "Sabtu",
    1 => "Minggu"
];

// default data 
$data = [
    ["hari" => "Senin", "total" => 0],
    ["hari" => "Selasa", "total" => 0],
    ["hari" => "Rabu", "total" => 0],
    ["hari" => "Kamis", "total" => 0],
    ["hari" => "Jumat", "total" => 0],
    ["hari" => "Sabtu", "total" => 0],
    ["hari" => "Minggu", "total" => 0],
];

// isi dari database
while ($row = mysqli_fetch_assoc($query)) {
    $hari_db = (int)$row['hari'];
    $namaHari = $mapHari[$hari_db];

    foreach ($data as &$item) {
        if ($item['hari'] === $namaHari) {
            $item['total'] = (int)$row['total'];
        }
    }
}

echo json_encode($data);