<?php
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';


$allowed_domains = [
    'http://localhost:5173',
    'https://report-in-fe.vercel.app'
];


if (in_array($origin, $allowed_domains)) {
    header("Access-Control-Allow-Origin: $origin");
}

header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With"); 
header("Content-Type: application/json; charset=utf-8");

// 1. MUNDUR 2 TINGKAT untuk mengambil vendor di folder utama (apireportin/vendor)
require __DIR__ . "/../../config/koneksi.php";
require '../../vendor/autoload.php';

use Dotenv\Dotenv;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;


// ========================================
// LOAD ENVIRONMENT VARIABLES
// ========================================
$dotenv = Dotenv::createImmutable(__DIR__ . "/../../");
$dotenv->safeLoad();


$SECRET_KEY_JWT = getenv('SECRET_KEY_JWT');


if (!$SECRET_KEY_JWT) {
    $SECRET_KEY_JWT = $_ENV['SECRET_KEY_JWT'] ?? null;
}


$apiKey = getenv('GEMINI_API_KEY');

if (!$apiKey) {

    $apiKey = $_ENV['GEMINI_API_KEY'] ?? null;
}

if (!$apiKey) {
    ob_clean(); 
    echo json_encode([
        "status" => "error",
        "reply" => "API Key tidak ditemukan di konfigurasi server."
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ========================================
// AMBIL USER ID DARI JWT & TENTUKAN STATUS LOGIN
// ========================================
$userId = null;
$status_login = "[STATUS: BELUM LOGIN]"; // Default status

$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

if (!empty($authHeader)) {
    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $token = $matches[1];

        try {
            
            $decoded = JWT::decode($token, new Key($SECRET_KEY_JWT, 'HS256'));
            
            // Ambil ID
            $userId = $decoded->id ?? $decoded->user_id ?? $decoded->data->id ?? null;
            
            // Jika ID berhasil didapatkan, ubah status menjadi sudah login
            if ($userId) {
                $status_login = "[STATUS: SUDAH LOGIN]";
            }

        } catch (Exception $e) {
            error_log("JWT Decode Error: " . $e->getMessage());
            // Status tetap [STATUS: BELUM LOGIN] jika token salah/expired
        }
    }
}

// ========================================
// VALIDASI METHOD
// ========================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
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

if (isset($_POST['message'])) {
    $message = trim($_POST['message']);
}

if (empty($message)) {
    $json = json_decode(file_get_contents("php://input"), true);
    if (isset($json['message'])) {
        $message = trim($json['message']);
    }
}

if (empty($message)) {
    ob_clean();
    echo json_encode([
        "status" => "error",
        "reply" => "Pesan tidak boleh kosong"
    ]);
    exit();
}

// ========================================
// SYSTEM PROMPT BARU DENGAN ATURAN LOGIN
// ========================================
$systemPrompt = "
Kamu adalah NOVA, AI Assistant resmi ReportIn.
ReportIn adalah aplikasi pelaporan masyarakat berbasis web.

--- STATUS LOGIN USER ---
Di setiap pesan, sistem akan menyisipkan indikator status user, yaitu: [STATUS: SUDAH LOGIN] atau [STATUS: BELUM LOGIN].
ATURAN LOGIN SANGAT KETAT:
1. Jika user meminta untuk dibuatkan laporan otomatis, kamu WAJIB mengecek status ini terlebih dahulu.
2. Jika statusnya [STATUS: BELUM LOGIN], kamu DILARANG KERAS memproses laporan, menanyakan detail, atau mengeluarkan JSON. Kamu WAJIB menyuruh user untuk Login atau Register.
    Contoh jawaban wajib jika belum login: 'Maaf, untuk dapat membuat laporan melalui NOVA, Anda harus Login terlebih dahulu ke dalam aplikasi. Silakan Login, atau lakukan Registrasi jika Anda belum memiliki akun.'
3. Jika statusnya [STATUS: SUDAH LOGIN], barulah kamu boleh memproses laporan sesuai aturan di bawah.

--- KNOWLEDGE BASE (TENTANG REPORTIN) ---
Gunakan informasi ini sebagai pedoman utama untuk menjawab pertanyaan seputar identitas, tujuan, dan fitur aplikasi:
1. Latar Belakang: ReportIn hadir di era digital untuk memastikan suara masyarakat terdengar dan membawa dampak. Kami memangkas jarak antara masyarakat dan pihak pengelola untuk menciptakan ekosistem yang responsif, transparan, dan akuntabel.
2. Visi: Menjadi platform digital terdepan yang menghubungkan kepedulian masyarakat dengan perbaikan lingkungan, demi terciptanya infrastruktur publik yang lebih layak dan merata bagi semua orang.
3. Misi: Memberdayakan masyarakat melalui teknologi pelaporan yang mudah dan transparan, sekaligus menyediakan data akurat bagi pihak terkait agar permasalahan fasilitas umum ditindaklanjuti dengan cepat dan tepat sasaran.
4. Fitur Utama ReportIn:
    - PRIVATE IDENTITY: Fitur pelaporan anonim.
    - FOKUS PADA PENGALAMAN PENGGUNA (UX).
    - PANTAU LAPORAN DALAM SATU KLIK.
    - CHATBOT AI (NOVA): Asisten AI interaktif yang memandu user dan membuat laporan otomatis melalui chat.
5. Slogan Utama: 'ReportIn – Suaramu, Kendali di Tanganmu.'

Fitur utama ReportIn: Membuat laporan masyarakat, Upload foto bukti, Tracking status laporan, Riwayat laporan, Statistik, Peta laporan.

ATURAN SANGAT KETAT (WAJIB PATUH):
1. JANGAN PERNAH bertele-tele. JANGAN PERNAH meminta konfirmasi ulang seperti 'Apakah Anda yakin?' atau 'Apakah ingin diproses sekarang?'.
2. Jika pesan user sudah memiliki informasi Judul, Deskripsi, Lokasi, dan Kategori, DAN statusnya [STATUS: SUDAH LOGIN], kamu WAJIB LANGSUNG membuat laporan dan mengeluarkan blok |||JSON|||.
3. Jika data dari user kurang (dan user sudah login), langsung tanyakan poin apa yang spesifik belum ada.
4. JANGAN PERNAH menyisipkan teks atau karakter apapun di dalam blok |||JSON||| selain format JSON yang sudah ditentukan.

--- KONDISI 1: JIKA USER BERTANYA 'CARA' ATAU PANDUAN MANUAL ---
Jika user bertanya cara menggunakan aplikasi, cara membuat laporan manual, atau pertanyaan seputar fitur:
- JANGAN meminta data laporan. 
- JANGAN mengeluarkan kode JSON.
- Jawablah dengan memberikan langkah-langkah yang jelas, terstruktur, dan ramah. (Boleh dijawab meskipun statusnya Belum Login).

--- KONDISI 2: JIKA USER INGIN DIBUATKAN LAPORAN OTOMATIS OLEH NOVA ---
Jika user memberikan kalimat yang mengandung data kejadian (misal: 'tolong laporkan...', 'ada kejadian...', 'judulnya...'):
- CEK STATUS LOGIN TERLEBIH DAHULU. Jika [STATUS: BELUM LOGIN], tolak dan suruh login/register.
- Jika [STATUS: SUDAH LOGIN] dan data kurang, langsung tanyakan poin apa yang spesifik belum ada.
- JIKA DATA SUDAH LENGKAP, kamu WAJIB LANGSUNG membuat laporan dan mengeluarkan blok |||JSON||| di akhir jawaban.

INSTRUKSI SANGAT PENTING:
Jika user sudah memberikan SEMUA data dan SUDAH LOGIN, kamu WAJIB menyisipkan sinyal rahasia di baris paling bawah jawabanmu dengan format persis seperti ini:
|||{\"judulLaporan\": \"[Judul dari user]\", \"deskripsi\": \"[Deskripsi dari user]\", \"kategoriLaporan\": \"[Kategori dari user]\", \"isiAlamat\": \"[Lokasi dari user]\"}|||
";

// ========================================
// PROMPT FINAL (Gabungkan status login dan pesan)
// ========================================
$finalPrompt = $systemPrompt . "\n\nUser: " . $status_login . " " . $message;

// ========================================
// REQUEST DATA FOR GEMINI API
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
        "maxOutputTokens" => 1000
    ]
];

function callGeminiAPI(string $modelName, string $apiKey, array $payload): array
{
    $url = "https://generativelanguage.googleapis.com/v1beta/models/" . $modelName . ":generateContent?key=" . $apiKey;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json"
    ],
    CURLOPT_POSTFIELDS => json_encode($payload),
    
    
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false
]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        curl_close($ch);
        return ['error' => true, 'message' => 'Gagal terhubung ke server AI'];
    }

    curl_close($ch);
    return ['error' => false, 'http_code' => $httpCode, 'body' => json_decode($response, true)];
}

$primaryModel = "gemini-2.5-flash";
$fallbackModel = "gemini-2.5-flash-8b"; // Diubah dari 3.1 lite karena 3.1 tidak tersedia via API biasa

$apiResult = callGeminiAPI($primaryModel, $apiKey, $data);

if (!$apiResult['error'] && $apiResult['http_code'] == 429) {
    file_put_contents("debug_ai.txt", "Peringatan: Kuota $primaryModel habis! Berganti otomatis ke $fallbackModel..." . PHP_EOL, FILE_APPEND);
    $apiResult = callGeminiAPI($fallbackModel, $apiKey, $data);
}

if ($apiResult['error']) {
    ob_clean();
    echo json_encode(["status" => "error", "reply" => $apiResult['message']]);
    exit();
}

$result = $apiResult['body'];

if (isset($result['error'])) {
    ob_clean();
    echo json_encode([
        "status" => "error",
        "reply" => "API Error: " . $result['error']['message']
    ]);
    exit();
}

$reply = "";

if (
    isset($result['candidates']) &&
    isset($result['candidates'][0]['content']) &&
    isset($result['candidates'][0]['content']['parts']) &&
    isset($result['candidates'][0]['content']['parts'][0]['text'])
) {
    $reply = $result['candidates'][0]['content']['parts'][0]['text'];
} else {
    ob_clean();
    echo json_encode([
        "status" => "error",
        "reply" => "Format response Gemini berubah",
        "debug" => $result
    ]);
    exit();
}

file_put_contents("rekaman_nova.txt", "============= \nPESAN USER: " . $status_login . " " . $message . "\n\nJAWABAN NOVA:\n" . $reply . "\n\n", FILE_APPEND);

// ========================================
// DETEKSI PEMBUATAN LAPORAN OTOMATIS
// ========================================
if (preg_match('/\|\|\|(.*?)\|\|\|/s', $reply, $matches)) {
    
    // BACKEND SECURITY: Pastikan yang eksekusi ini memiliki $userId (sudah login)
    if ($userId) {

    global $conn; // Pastikan koneksi database tersedia
        
        // CATATAN: JANGAN ADALAKAN require ATAU global $conn DI SINI! Biarkan kosong saja.

        $jsonSignal = $matches[1];
        $reportData = json_decode($jsonSignal, true);

        file_put_contents("debug_nova.txt", "Sinyal Diterima: " . $jsonSignal . PHP_EOL, FILE_APPEND);

        if ($reportData) {
            // Ambil data dari AI dan amankan dari SQL Injection
            $judulLaporan    = mysqli_real_escape_string($conn, $reportData['judulLaporan'] ?? 'Laporan Tanpa Judul');
            $deskripsi       = mysqli_real_escape_string($conn, $reportData['deskripsi'] ?? '');
            $kategoriLaporan = mysqli_real_escape_string($conn, $reportData['kategoriLaporan'] ?? 'Lainnya');
            $isiAlamat       = mysqli_real_escape_string($conn, $reportData['isiAlamat'] ?? '');

            $tanggalLaporan = date('Y-m-d');
            $gambarLaporan  = 'no-image.png';
            $status         = 'pending';

            $sql = "INSERT INTO reports 
                    (user_id, tanggalLaporan, judulLaporan, deskripsi, kategoriLaporan, isiAlamat, gambarLaporan, status) 
                    VALUES 
                    ('$userId', '$tanggalLaporan', '$judulLaporan', '$deskripsi', '$kategoriLaporan', '$isiAlamat', '$gambarLaporan', '$status')";

            $executeInsert = mysqli_query($conn, $sql);

            if (!$executeInsert) {
                error_log("Database Insert Error via MySQLi: " . mysqli_error($conn));
            }
        }
    } else {
        error_log("Peringatan: Mencoba insert data padahal belum login.");
    }

    $reply = preg_replace('/\|\|\|.*?\|\|\|/s', '', $reply);
    $reply = trim($reply);
}

// Bagian ini akan memastikan React selalu menerima JSON dan tidak akan memicu 'Unexpected end of JSON' lagi!
ob_clean();
echo json_encode([
    "status" => "success",
    "reply" => $reply
]);
exit();