<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Mpdf\Mpdf;

$mpdf = new Mpdf(['mode' => 'utf-8']);
$mpdf->showImageErrors = true;

$mpdf->WriteHTML('<h1>Debug Cover Image</h1>');

$imagePath = __DIR__ . '/../originals/content/assets/images/abf_cover_optimized.jpg';

if (file_exists($imagePath)) {
    $mpdf->WriteHTML('<p>Image found at: ' . $imagePath . '</p>');
    // Try standard image
    $mpdf->WriteHTML('<img src="' . $imagePath . '" width="500" />');
} else {
    $mpdf->WriteHTML('<p style="color:red">Image NOT found at: ' . $imagePath . '</p>');
}

$mpdf->Output(__DIR__ . '/../public/debug_cover.pdf', \Mpdf\Output\Destination::FILE);
echo "Debug PDF generated at public/debug_cover.pdf\n";
