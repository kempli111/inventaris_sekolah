<?php
session_start();
require_once 'config.php'; // $conn = new mysqli(...)

header('Content-Type: application/json; charset=utf-8');

// Hanya terima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}


// Pastikan JSON
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') === false) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Bad Request: Content-Type harus application/json']);
    exit;
}

// Decode body
$rawBody = file_get_contents('php://input');
$input   = json_decode($rawBody, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON payload']);
    exit;
}

$userMessage = trim($input['message'] ?? '');
if ($userMessage === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Field "message" tidak boleh kosong']);
    exit;
}

try {
    // Dapatkan statistik
    $stats = getInventoryStats($conn);
    
    // Buat prompt untuk Llama
    $prompt = createLlamaPrompt($stats, $userMessage);
    
    // Panggil Hugging Face API dengan model Llama
    $response = callLlamaAPI($prompt);
    
    echo json_encode([
        'success' => true,
        'response' => $response
    ]);

} catch (Exception $e) {
    // Log error detail ke error_log
    error_log("[chatbot.php] ERROR: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal Server Error: ' . $e->getMessage()
    ]);
}
exit;


// ——————————————————————————
// Fungsi: Ambil statistik inventaris
function getInventoryStats(mysqli $conn): array {
    $keys = [
        'total_items'      => "SELECT COUNT(*) AS total FROM items",
        'active_loans'     => "SELECT COUNT(*) AS total FROM peminjaman WHERE status = 'dipinjam'",
        'broken_items'     => "SELECT COUNT(*) AS total FROM items WHERE item_condition IN ('rusak ringan','rusak berat')",
        'total_categories' => "SELECT COUNT(*) AS total FROM categories",
        'total_rooms'      => "SELECT COUNT(*) AS total FROM rooms"
    ];
    $stats = [];

    foreach ($keys as $k => $sql) {
        if (!$res = $conn->query($sql)) {
            throw new Exception("DB query failed for {$k}: " . $conn->error);
        }
        $row = $res->fetch_assoc();
        $stats[$k] = (int)($row['total'] ?? 0);
        $res->free();
    }
    return $stats;
}

// Fungsi: Buat prompt untuk Llama
function createLlamaPrompt(array $stats, string $userMessage): string {
    return "Kamu adalah asisten SIASET (Sistem Informasi Aset) yang membantu mengelola inventaris. " .
           "Berikut statistik saat ini:\n" .
           "- Total barang: {$stats['total_items']}\n" .
           "- Peminjaman aktif: {$stats['active_loans']}\n" .
           "- Barang rusak: {$stats['broken_items']}\n" .
           "- Total kategori: {$stats['total_categories']}\n" .
           "- Total ruangan: {$stats['total_rooms']}\n\n" .
           "Panduan peminjaman:\n" .
           "1. Login ke sistem\n" .
           "2. Pilih menu Peminjaman\n" .
           "3. Pilih barang yang akan dipinjam\n" .
           "4. Isi form peminjaman\n" .
           "5. Tunggu persetujuan admin\n\n" .
           "Pertanyaan user: " . $userMessage . "\n\n" .
           "Berikan jawaban yang sopan dan informatif dalam Bahasa Indonesia.";
}

// Fungsi: Panggil Llama API
function callLlamaAPI(string $prompt): string {
    $API_TOKEN = "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";
    // Menggunakan model GPT2 yang sudah fine-tuned untuk dialog
    $API_URL = "https://api-inference.huggingface.co/models/PygmalionAI/pygmalion-350m";

    $headers = [
        "Authorization: Bearer " . $API_TOKEN,
        "Content-Type: application/json"
    ];

    // Format prompt untuk model dialog
    $data = [
        "inputs" => "Human: " . $prompt . "\nAssistant: ",
        "parameters" => [
            "max_length" => 100,
            "temperature" => 0.8,
            "top_p" => 0.9,
            "repetition_penalty" => 1.2,
            "do_sample" => true
        ]
    ];

    $ch = curl_init($API_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => false
    ]);

    $maxRetries = 2;
    $attempt = 0;
    $response = false;
    $lastError = '';

    while ($attempt < $maxRetries && $response === false) {
        $response = curl_exec($ch);
        if ($response === false) {
            $lastError = curl_error($ch);
            $attempt++;
            if ($attempt < $maxRetries) {
                usleep(500000); // Tunggu 0.5 detik
            }
        }
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    error_log("API Response Code: " . $httpCode);
    error_log("API Raw Response: " . $response);

    if ($response === false) {
        return "Maaf, sistem sedang sibuk. Silakan coba beberapa saat lagi.";
    }

    try {
        $result = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        
        if (empty($result)) {
            return handleDefaultResponse($prompt);
        }

        // Ekstrak teks dari response
        $botResponse = '';
        if (isset($result[0]['generated_text'])) {
            $botResponse = $result[0]['generated_text'];
            // Bersihkan response, ambil hanya bagian Assistant
            if (strpos($botResponse, 'Assistant:') !== false) {
                $parts = explode('Assistant:', $botResponse);
                $botResponse = trim(end($parts));
            }
        }

        if (empty($botResponse)) {
            return handleDefaultResponse($prompt);
        }

        return trim($botResponse);

    } catch (Exception $e) {
        error_log("JSON Parse Error: " . $e->getMessage());
        return handleDefaultResponse($prompt);
    }
}

// Fungsi untuk memberikan respons default berdasarkan kata kunci
function handleDefaultResponse(string $prompt): string {
    $prompt = strtolower($prompt);
    
    if (strpos($prompt, 'total barang') !== false) {
        return "Berdasarkan data terakhir, total barang dalam inventaris adalah " . getStats()['total_items'] . " item.";
    }
    
    if (strpos($prompt, 'peminjaman') !== false) {
        return "Saat ini terdapat " . getStats()['active_loans'] . " peminjaman yang aktif.";
    }
    
    if (strpos($prompt, 'rusak') !== false) {
        return "Terdapat " . getStats()['broken_items'] . " barang yang dalam kondisi rusak.";
    }
    
    if (strpos($prompt, 'kategori') !== false) {
        return "Sistem memiliki " . getStats()['total_categories'] . " kategori barang yang berbeda.";
    }
    
    if (strpos($prompt, 'ruang') !== false || strpos($prompt, 'ruangan') !== false) {
        return "Total ruangan yang terdaftar adalah " . getStats()['total_rooms'] . " ruangan.";
    }
    
    return "Saya dapat membantu Anda dengan informasi tentang:\n" .
           "- Total barang inventaris\n" .
           "- Status peminjaman\n" .
           "- Barang rusak\n" .
           "- Kategori barang\n" .
           "- Informasi ruangan\n" .
           "Silakan tanyakan yang Anda ingin ketahui.";
}

// Fungsi untuk mendapatkan statistik
function getStats(): array {
    global $conn;
    $stats = getInventoryStats($conn);
    return $stats;
}
