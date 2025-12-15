<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Mpdf\Mpdf;

$mpdf = new Mpdf(['showImageErrors' => true]);
$imagePath = realpath(__DIR__ . '/../originals/content/assets/images/abf_cover2.png');

echo "Testing image: $imagePath\n";

if (file_exists($imagePath)) {
    echo "File exists.\n";
} else {
    echo "File does NOT exist.\n";
}

$mpdf->WriteHTML("<h1>Image Test</h1>");
$mpdf->WriteHTML("<img src='$imagePath' width='500' />");

$mpdf->Output(__DIR__ . '/../public/test_image.pdf', \Mpdf\Output\Destination::FILE);
echo "Test PDF generated at public/test_image.pdf\n";
