<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Mpdf\Mpdf;

// Minimal setup
$mpdf = new Mpdf(['mode' => 'utf-8']);
$mpdf->showImageErrors = true;

$mpdf->WriteHTML('<h1>Debug PNG Cover</h1>');

// Original PNG path
$imagePath = __DIR__ . '/../originals/content/assets/images/abf_cover.png';

if (file_exists($imagePath)) {
    $mpdf->WriteHTML('<p>Image found at: ' . $imagePath . '</p>');
    // Simple img tag, no styling
    $mpdf->WriteHTML('<img src="' . $imagePath . '" width="500" />');
} else {
    $mpdf->WriteHTML('<p style="color:red">Image NOT found at: ' . $imagePath . '</p>');
}

$outputFile = __DIR__ . '/../public/debug_png.pdf';
$mpdf->Output($outputFile, \Mpdf\Output\Destination::FILE);

echo "Debug PNG generated at: $outputFile\n";
