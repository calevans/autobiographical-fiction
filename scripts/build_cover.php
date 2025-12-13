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
$trimHeightIn = 8.5; // Updated to 8.5" per Lulu guide
$wrapIn = 0.625; // Uniform wrap of 0.625" per Lulu guide
$paperThicknessIn = 0.002252;
// $spineWidthIn = $pageCount * $paperThicknessIn;
$spineWidthIn = 0.25; // Fixed spine width as requested

$totalWidthIn = $wrapIn + $trimWidthIn + $spineWidthIn + $trimWidthIn + $wrapIn;
$totalHeightIn = $wrapIn + $trimHeightIn + $wrapIn;

$mmPerInch = 25.4;
$totalWidthMm = $totalWidthIn * $mmPerInch;
$totalHeightMm = $totalHeightIn * $mmPerInch;
$trimWidthMm = $trimWidthIn * $mmPerInch;

$backWidthMm = ($wrapIn + $trimWidthIn) * $mmPerInch;
$spineWidthMm = $spineWidthIn * $mmPerInch;
$frontWidthMm = ($trimWidthIn + $wrapIn) * $mmPerInch;
$heightMm = $totalHeightMm;

$spineX = $backWidthMm;
$frontX = $spineX + $spineWidthMm;

// 2. Image Path
$imagePath = realpath(__DIR__ . '/../originals/content/assets/images/abf_cover3.png');
if (!$imagePath) {
    die("Image not found!");
}

// 3. Content
$backCoverMd = __DIR__ . '/../content/back_cover.md';
$backCoverHtml = '';
if (file_exists($backCoverMd)) {
    $Parsedown = new Parsedown();
    $backCoverHtml = $Parsedown->text(file_get_contents($backCoverMd));
}

// 4. Generate PDF
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

// Calculate Back Cover Text Position
$backTextWidthMm = $backWidthMm * 0.7;
$backTextLeftMm = ($backWidthMm - $backTextWidthMm) / 2;
$backTextTopMm = $heightMm * 0.15;

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
        background-size: 104%; /* Zoom in 4% */
        background-position: center center;
    }
    .front-title {
        position: absolute;
        top: 30mm;
        left: ' . $frontX . 'mm;
        width: ' . $trimWidthMm . 'mm;
        text-align: center;
        color: yellow;
        font-size: 36pt;
        font-family: sans-serif;
        font-weight: bold;
        text-shadow: 3px 3px 6px #000000;
        z-index: 1000;
    }
    .front-byline {
        position: absolute;
        bottom: 25mm; /* Moved up to clear the 0.625" (15.87mm) wrap area */
        left: ' . $frontX . 'mm;
        width: ' . $trimWidthMm . 'mm;
        text-align: center;
        color: white;
        font-size: 24pt;
        font-family: sans-serif;
        font-weight: bold;
        text-shadow: 3px 3px 6px #000000;
        z-index: 1000;
    }
    .back-text-box {
        position: absolute;
        top: ' . $backTextTopMm . 'mm;
        left: ' . $backTextLeftMm . 'mm;
        width: ' . $backTextWidthMm . 'mm;
        background-color: rgba(0, 0, 0, 0.6);
        color: #FFFFFF;
        padding: 5mm;
        border-radius: 3mm;
        font-size: 11pt;
        line-height: 1.5;
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

<!-- Back Cover Text -->
<div class="back-text-box">
    ' . $backCoverHtml . '
</div>
';

$mpdf->WriteHTML($html);

$outputFile = __DIR__ . '/../public/cover.pdf';
$mpdf->Output($outputFile, \Mpdf\Output\Destination::FILE);

echo "Generated cover at $outputFile\n";
echo "Dimensions: Total {$totalWidthMm}mm x {$totalHeightMm}mm\n";
echo "Spine: {$spineWidthMm}mm at X={$spineX}mm\n";
