<?php
if (isset($_GET['code'])) {
    $code = $_GET['code'];

    // Pastikan folder barcodes/ ada
    $dir = "barcodes/";
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    // Set ukuran gambar barcode
    $width = 300;
    $height = 100;
    $image = imagecreate($width, $height);
    $background = imagecolorallocate($image, 255, 255, 255);
    $black = imagecolorallocate($image, 0, 0, 0);

    // Tambahkan garis barcode
    $x = 10;
    for ($i = 0; $i < strlen($code); $i++) {
        $barWidth = ($code[$i] % 2 == 0) ? 4 : 2;
        imagefilledrectangle($image, $x, 10, $x + $barWidth, 90, $black);
        $x += $barWidth + 2;
    }

    // Tambahkan teks kode di bawah barcode
    imagestring($image, 5, 10, 80, $code, $black);

    // Simpan gambar ke dalam folder barcodes/
    $file_path = $dir . $code . ".png";
    imagepng($image, $file_path);
    imagedestroy($image);

    // Tampilkan gambar yang sudah disimpan
    header("Content-Type: image/png");
    readfile($file_path);
}
?>
