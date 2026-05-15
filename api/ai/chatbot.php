<?php
require 'vendor/autoload.php';

use Dotenv\Dotenv;

// ========================================
// LOAD ENVIRONMENT VARIABLES
// ========================================
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['GEMINI_API_KEY'] ?? null;

if (!$apiKey) {
    echo json_encode([
        "status" => "error",
        "reply" => "API Key tidak ditemukan di konfigurasi server."
    ]);
    exit();
}

// ========================================
// CORS
// ========================================
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// ========================================
// HANDLE OPTIONS
// ========================================
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ========================================
// VALIDASI METHOD
// ========================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    echo json_encode([
        "status" => "error",
        "reply" => "Method tidak diizinkan"
    ]);

    exit();
}

// ========================================
// AMBIL MESSAGE
// ========================================

$message = "";

// FORM DATA
if (isset($_POST['message'])) {
    $message = trim($_POST['message']);
}

// JSON
if (empty($message)) {

    $json = json_decode(file_get_contents("php://input"), true);

    if (isset($json['message'])) {
        $message = trim($json['message']);
    }
}

// ========================================
// VALIDASI MESSAGE
// ========================================
if (empty($message)) {

    echo json_encode([
        "status" => "error",
        "reply" => "Pesan tidak boleh kosong"
    ]);

    exit();
}

// ========================================
// API KEY
// ========================================

$apiKey = $_ENV['GEMINI_API_KEY'] ?? null;

// ========================================
// URL GEMINI
// ========================================

$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $apiKey;

// ========================================
// SYSTEM PROMPT
// ========================================

$systemPrompt = "
Kamu adalah AI Assistant resmi untuk aplikasi ReportIn.

ReportIn adalah aplikasi pelaporan masyarakat berbasis web.

Fitur utama ReportIn:
- Membuat laporan masyarakat
- Upload foto bukti
- Tracking status laporan
- Riwayat laporan
- Statistik laporan
- Peta laporan

Tugas kamu:
- Membantu user memahami fitur aplikasi
- Membantu user membuat laporan 
- membantu user membuat laporan secara otomatis
- Menjawab pertanyaan tentang aplikasi
- Menjawab dengan ramah dan profesional
- Gunakan bahasa Indonesia
- Jawaban singkat dan jelas
- Jangan buat jawaban yang tidak relevan dengan aplikasi ReportIn
- Jika ada yang tidak kamu ketahui, jawab dengan jujur bahwa kamu tidak tahu
- Jika user meminta kamu untuk membuat laporan, tanyakan detail laporan seperti lokasi, deskripsi, dan foto bukti. Kemudian buat laporan tersebut secara otomatis untuk user.
- Jangan buat laporan yang tidak jelas atau tidak lengkap. Pastikan laporan yang kamu buat memiliki detail yang cukup untuk diproses oleh tim ReportIn.
- Jika ada pertanyaan yang tidak relevan dengan aplikasi ReportIn, jawab dengan sopan bahwa kamu hanya bisa membantu dengan pertanyaan seputar aplikasi ReportIn.
";

// ========================================
// PROMPT FINAL
// ========================================

$finalPrompt = $systemPrompt . "\n\nUser: " . $message;

// ========================================
// REQUEST DATA
// ========================================

$data = [
    "contents" => [
        [
            "parts" => [
                [
                    "text" => $finalPrompt
                ]
            ]
        ]
    ],
    "generationConfig" => [
        "temperature" => 0.7,
        "maxOutputTokens" => 300
    ]
];

// ========================================
// CURL
// ========================================

$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json"
    ],
    CURLOPT_POSTFIELDS => json_encode($data)
]);

$response = curl_exec($ch);

// ========================================
// ERROR CURL
// ========================================

if (curl_errno($ch)) {

    echo json_encode([
        "status" => "error",
        "reply" => "Gagal terhubung ke AI"
    ]);

    curl_close($ch);
    exit();
}

curl_close($ch);

// ========================================
// DEBUG RESPONSE
// ========================================

$result = json_decode($response, true);

// ========================================
// ERROR GEMINI
// ========================================

if (isset($result['error'])) {

    echo json_encode([
        "status" => "error",
        "reply" => $result['error']['message']
    ]);

    exit();
}

// ========================================
// CEK RESPONSE GEMINI
// ========================================

$reply = "";

if (
    isset($result['candidates']) &&
    isset($result['candidates'][0]['content']) &&
    isset($result['candidates'][0]['content']['parts']) &&
    isset($result['candidates'][0]['content']['parts'][0]['text'])
) {

    $reply = $result['candidates'][0]['content']['parts'][0]['text'];

} else {

    // DEBUG RESPONSE ASLI
    echo json_encode([
        "status" => "error",
        "reply" => "Format response Gemini berubah",
        "debug" => $result
    ]);

    exit();
}

// ========================================
// FORMAT TEXT
// ========================================

$reply = nl2br($reply);

// ========================================
// RESPONSE
// ========================================

echo json_encode([
    "status" => "success",
    "reply" => $reply
]);

exit();

?>