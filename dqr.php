<?php
require 'config.php';
require 'vendor/autoload.php';
require 'phpqrcode/qrlib.php';
require_once('vendor/setasign/fpdf/fpdf.php');
use Intervention\Image\ImageManagerStatic as Image;

// Set GD sebagai driver
Image::configure(['driver' => 'gd']);

// Pastikan Room ID diberikan
if (!isset($_GET['id'])) {
    die("Room ID tidak ditemukan");
}

$room_id = $_GET['id'];

// Ambil data barang berdasarkan room_id
$query = "SELECT i.id, i.name as item_name, r.name as room_name, i.item_code 
          FROM items i 
          JOIN rooms r ON i.room_id = r.id 
          WHERE i.room_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $room_id);
$stmt->execute();
$result = $stmt->get_result();

// Buat direktori temporary jika belum ada
if (!file_exists('temp')) {
    mkdir('temp', 0777, true);
}
if (!file_exists('temp/qr')) {
    mkdir('temp/qr', 0777, true);
}

/**
 * Fungsi untuk membuat template QR code dengan tampilan yang lebih rapi.
 *
 * @param string $qrPath Path file QR code yang sudah dibuat.
 * @param string $itemName Nama barang.
 * @param string $roomName Nama ruangan.
 * @param string $itemCode Kode barang.
 * @return \Intervention\Image\Image
 */
function createQRTemplate($qrPath, $itemName, $roomName, $itemCode) {
    // Buat kanvas template lebih kecil untuk muat 6 template
    $template = Image::canvas(300, 200, '#ffffff');
    
    // Border utama: kotak luar dengan border hitam
    $template->rectangle(0, 0, 299, 199, function ($draw) {
        $draw->border(2, '#000000');
    });
    
    // Garis pemisah vertikal menggunakan rectangle
    $template->rectangle(119, 0, 2, 200, function ($draw) {
        $draw->background('#000000');
    });
    
    // Load dan tambahkan QR code di sebelah kiri
    $qr = Image::make($qrPath);
    $qr->resize(100, 100);
    $template->insert($qr, 'left', 10, 50);
    
    // Load dan tambahkan logo
    if (file_exists('sa.png')) {
        $logo = Image::make('sa.png');
        $logo->resize(30, 30);
        $template->insert($logo, 'top-right', 10, 10);
    }
    
    // Informasi di sebelah kanan dengan font lebih besar
    // Kode Barang
    $template->text('Kode:', 130, 40, function($font) {
        $font->size(16);
        $font->color('#000000');
    });
    $template->text($itemCode, 130, 60, function($font) {
        $font->size(18);
        $font->color('#000000');
    });
    
    // Nama Barang
    $template->text('Nama:', 130, 90, function($font) {
        $font->size(16);
        $font->color('#000000');
    });
    $template->text($itemName, 130, 110, function($font) {
        $font->size(18);
        $font->color('#000000');
    });
    
    // Lokasi
    $template->text('Lokasi:', 130, 140, function($font) {
        $font->size(16);
        $font->color('#000000');
    });
    $template->text($roomName, 130, 160, function($font) {
        $font->size(18);
        $font->color('#000000');
    });
    
    return $template;
}

// Buat PDF baru dengan orientasi landscape dan ukuran A4
$pdf = new FPDF('L', 'mm', 'A4');
$pdf->AddPage();

// Hitung ukuran dan margin untuk 6 template per halaman (2x3)
$pageWidth = 297; // Lebar halaman A4 landscape
$pageHeight = 210; // Tinggi halaman A4 landscape
$templateWidth = 95; // Lebar template dalam mm
$templateHeight = 65; // Tinggi template dalam mm
$marginX = 6; // Margin horizontal
$marginY = 7; // Margin vertical

$currentTemplate = 0;
$positions = [
    [6, 7],       // Baris 1, Kolom 1
    [101, 7],     // Baris 1, Kolom 2
    [196, 7],     // Baris 1, Kolom 3
    [6, 72],      // Baris 2, Kolom 1
    [101, 72],    // Baris 2, Kolom 2
    [196, 72]     // Baris 2, Kolom 3
];

// Loop data barang dan buat setiap template kemudian tambahkan ke PDF
while ($row = $result->fetch_assoc()) {
    // Generate QR code file dengan URL lengkap
    $qrPath = "temp/qr/" . $row['id'] . ".png";
    $url = "http://" . $_SERVER['HTTP_HOST'] . "/detail_barang.php?id=" . $row['id'];
    QRcode::png($url, $qrPath, QR_ECLEVEL_L, 10);
    
    // Buat template dengan QR code dan data barang
    $template = createQRTemplate($qrPath, $row['item_name'], $row['room_name'], $row['item_code']);
    $templatePath = "temp/qr/" . $row['id'] . "_template.png";
    $template->save($templatePath);
    
    // Ambil posisi untuk template saat ini
    $position = $positions[$currentTemplate];
    
    // Masukkan template ke halaman PDF
    $pdf->Image($templatePath, $position[0], $position[1], $templateWidth, $templateHeight);
    
    $currentTemplate++;
    
    // Jika sudah 6 template, buat halaman baru
    if ($currentTemplate >= 6) {
        $currentTemplate = 0;
        $pdf->AddPage();
    }
}

// Output file PDF
$pdf->Output('D', 'qr_codes_room_' . $room_id . '.pdf');

// Bersihkan file temporary
array_map('unlink', glob("temp/qr/*"));
?>
