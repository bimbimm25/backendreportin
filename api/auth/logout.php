<?php
require __DIR__ . "/../../config/cors.php";
header("Content-Type: application/json");


echo json_encode(["message" => "Logout berhasil"]);