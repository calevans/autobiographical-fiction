<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Mpdf\Mpdf;

// 1. Setup Dimensions
$pageCountFile = __DIR__ . '/../logs/page_count.txt';
if (file_exists($pageCountFile)) {
    $pageCount = (int)file_get_contents($pageCountFile);
} else {
    $pageCount = 82; // Fallback
}

$trimWidthIn = 5.25;
$trimHeightIn = 8.25;
$wrapIn = 0.625;
$paperThicknessIn = 0.002252;
// $spineWidthIn = $pageCount * $paperThicknessIn;
$spineWidthIn = 0.25; // Fixed spine width as requested

$totalWidthIn = $wrapIn + $trimWidthIn + $spineWidthIn + $trimWidthIn + $wrapIn;
$totalHeightIn = $wrapIn + $trimHeightIn + $wrapIn;

$mmPerInch = 25.4;
$totalWidthMm = $totalWidthIn * $mmPerInch;
$totalHeightMm = $totalHeightIn * $mmPerInch;

$backWidthMm = ($wrapIn + $trimWidthIn) * $mmPerInch;
$spineWidthMm = $spineWidthIn * $mmPerInch;
$frontWidthMm = ($trimWidthIn + $wrapIn) * $mmPerInch;
$heightMm = $totalHeightMm;

$spineX = $backWidthMm;
$frontX = $spineX + $spineWidthMm;

// 2. Image Path
$imagePath = realpath(__DIR__ . '/../originals/content/assets/images/abf_cover2.png');
if (!$imagePath) {
    die("Image not found!");
}

// 3. Generate PDF
$mpdf = new Mpdf([
    'mode' => 'utf-8',
    'format' => [$totalWidthMm, $totalHeightMm],
    'margin_left' => 0,
    'margin_right' => 0,
    'margin_top' => 0,
    'margin_bottom' => 0,
    'img_dpi' => 300
]);

$mpdf->SetTitle('Autobiographical Fiction - Hardback Cover');

// HTML Structure
// Back Cover | Spine | Front Cover
$html = '
<style>
    @page { margin: 0; padding: 0; }
    body { margin: 0; padding: 0; background-color: #ffffff; }
    .cover-panel {
        position: absolute;
        top: 0;
        height: ' . $heightMm . 'mm;
        background-image: url("' . $imagePath . '");
        background-repeat: no-repeat;
        background-size: 102%; /* Zoom in 2% */
        background-position: center center;
    }
    .front-title {
        position: absolute;
        top: 30mm;
        left: ' . $frontX . 'mm;
        width: ' . $frontWidthMm . 'mm;
        text-align: center;
        color: yellow;
        font-size: 42pt;
        font-family: sans-serif;
        font-weight: bold;
        text-shadow: 3px 3px 6px #000000;
        z-index: 1000;
    }
    .front-byline {
        position: absolute;
        bottom: 25mm;
        left: ' . $frontX . 'mm;
        width: ' . $frontWidthMm . 'mm;
        text-align: center;
        color: white;
        font-size: 24pt;
        font-family: sans-serif;
        font-weight: bold;
        text-shadow: 3px 3px 6px #000000;
        z-index: 1000;
    }
</style>

<!-- Back Cover Image -->
<div class="cover-panel" style="left: 0; width: ' . $backWidthMm . 'mm;"></div>

<!-- Spine -->
<div style="position: absolute; top: 0; left: ' . $spineX . 'mm; width: ' . $spineWidthMm . 'mm; height: ' . $heightMm . 'mm; background-color: #000000;"></div>

<!-- Front Cover Image -->
<div class="cover-panel" style="left: ' . $frontX . 'mm; width: ' . $frontWidthMm . 'mm;"></div>

<!-- Front Title -->
<div class="front-title">Autobiographical Fiction</div>
<div class="front-byline">Cal Evans</div>
';

$mpdf->WriteHTML($html);

$outputFile = __DIR__ . '/../public/cover.pdf';
$mpdf->Output($outputFile, \Mpdf\Output\Destination::FILE);

echo "Generated cover at $outputFile\n";
echo "Dimensions: Total {$totalWidthMm}mm x {$totalHeightMm}mm\n";
echo "Spine: {$spineWidthMm}mm at X={$spineX}mm\n";
