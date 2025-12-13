<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Mpdf\Mpdf;

// Book Configuration
// Lulu Novella: 5.25" x 8.25"
// 1 inch = 25.4 mm
$width = 5.25 * 25.4;   // 133.35
$height = 8.25 * 25.4; // 209.55
$format = [$width, $height];

// Margins (mm)
$margin = 15.875;

$outputDir = __DIR__ . '/../public';
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0777, true);
}


// =========================================================
// GENERATE INTERIOR PDF
// =========================================================
echo "Generating Interior PDF...\n";

$mpdf = new Mpdf([
    'mode' => 'utf-8',
    'format' => $format,
    'margin_left' => $margin,
    'margin_right' => $margin,
    'margin_top' => $margin,
    'margin_bottom' => $margin,
    'mirrorMargins' => true // Standard for books
]);

$mpdf->SetTitle('Autobiographical Fiction');
$mpdf->SetAuthor('Cal Evans');

// Page Numbers
$mpdf->SetHTMLFooter('<div style="font-family: serif; font-size: 10pt; text-align: right;">{PAGENO}</div>', 'O');
$mpdf->SetHTMLFooter('<div style="font-family: serif; font-size: 10pt; text-align: left;">{PAGENO}</div>', 'E');

// Styles
$stylesheet = '
body { font-family: "Times New Roman", serif; font-size: 11pt; font-weight: normal; line-height: 1.4; text-align: justify; }
h1 { font-family: sans-serif; font-size: 18pt; text-align: center; font-weight: bold; margin-top: 10mm; margin-bottom: 5mm; }
.chapter-byline { font-family: sans-serif; font-size: 12pt; text-align: center; font-style: italic; margin-bottom: 10mm; }
.center-image { text-align: center; margin-top: 30mm; }
.dedication { text-align: center; font-style: italic; padding-top: 50mm; }
.title-page { text-align: center; padding-top: 50mm; }
.title-page h1 { font-size: 24pt; margin-bottom: 10mm; }
.title-page .author { font-size: 16pt; }
';
$mpdf->WriteHTML($stylesheet, \Mpdf\HTMLParserMode::HEADER_CSS);

// ---------------------------------------------------------
// 1. Title Page
// ---------------------------------------------------------
// Starts on Page 1 (Odd/Right)
$mpdf->WriteHTML('
<div class="title-page">
    <h1>Autobiographical Fiction</h1>
    <div class="author">by Cal Evans</div>
</div>
');

// ---------------------------------------------------------
// 2. Dedication
// ---------------------------------------------------------
// Force Next Odd Page
$mpdf->WriteHTML('<pagebreak type="NEXT-ODD" />');

$dedicationFile = __DIR__ . '/../content/dedication.md';
if (file_exists($dedicationFile)) {
    $content = file_get_contents($dedicationFile);
    $parts = explode('---', $content);
    $body = count($parts) >= 3 ? implode('---', array_slice($parts, 2)) : $content;

    $Parsedown = new Parsedown();
    $dedicationHtml = $Parsedown->text($body);

    $mpdf->WriteHTML('<div class="dedication">' . $dedicationHtml . '</div>');
}

// ---------------------------------------------------------
// 3. Table of Contents
// ---------------------------------------------------------
// Force Next Odd Page
$mpdf->WriteHTML('<pagebreak type="NEXT-ODD" />');

// mPDF TOC
$mpdf->TOCpagebreakByArray([
    'links' => true,
    'toc-prehtml' => '<h1>Table of Contents</h1>',
    'toc-font' => 'Times New Roman',
    'toc-size' => 12,
    'toc-indent' => 5,
]);

// ---------------------------------------------------------
// 4. Stories
// ---------------------------------------------------------
$files = glob(__DIR__ . '/../content/*.md');
$stories = [];

foreach ($files as $file) {
    $filename = basename($file);
    if ($filename === 'index.md' || $filename === 'dedication.md') {
        continue;
    }

    $content = file_get_contents($file);
    $parts = explode('---', $content);
    if (count($parts) < 3) {
        continue;
    }

    $frontmatter = $parts[1];
    $body = implode('---', array_slice($parts, 2));

    $title = '';
    if (preg_match('/title:\s*"(.*?)"/', $frontmatter, $matches)) {
        $title = $matches[1];
    }

    $image = '';
    if (preg_match('/image:\s*"(.*?)"/', $frontmatter, $matches)) {
        $image = $matches[1];
    }

    $age = 999;
    if (preg_match('/Age (\d+):/', $title, $matches)) {
        $age = (int)$matches[1];
    }

    // Extract Byline
    $byline = '';
    // Look for **By ...** at the start of the body
    if (preg_match('/^\s*\*\*(By.*?)\*\*\s*/s', $body, $matches)) {
        $byline = $matches[1];
        // Remove it from body
        $body = str_replace($matches[0], '', $body);
    }

    $stories[] = [
        'age' => $age,
        'title' => $title,
        'byline' => $byline,
        'image' => $image,
        'body' => $body,
        'file' => $file
    ];
}

usort($stories, function($a, $b) {
    return $a['age'] <=> $b['age'];
});

$Parsedown = new Parsedown();

foreach ($stories as $story) {
    // -----------------------------------------------------
    // Image Page (Left / Even)
    // -----------------------------------------------------
    // Ensure we start the chapter sequence on a spread: Image on Left, Title on Right.
    $mpdf->WriteHTML('<pagebreak type="NEXT-EVEN" />');

    // Add to TOC (Level 0)
    $mpdf->TOC_Entry($story['title'], 0);

    if ($story['image']) {
        // Convert assets/images/foo.jpg to originals/content/assets/images/foo.png
        $imageName = basename($story['image']);
        $imageNamePng = str_replace('.jpg', '.png', $imageName);
        $imagePath = realpath(__DIR__ . '/../originals/content/assets/images/' . $imageNamePng);

        if ($imagePath && file_exists($imagePath)) {
            echo "Adding image: $imageNamePng\n";
            $mpdf->WriteHTML('<div class="center-image"><img src="' . $imagePath . '" width="75mm" /></div>');
        } else {
             echo "MISSING IMAGE: $imageNamePng (Original: {$story['image']})\n";
             $mpdf->WriteHTML('<div style="height: 50mm;">&nbsp;</div>');
        }
    } else {
        echo "No image defined for {$story['title']}\n";
        $mpdf->WriteHTML('<div style="height: 50mm;">&nbsp;</div>');
    }

    // Title
    $mpdf->WriteHTML('<h1>' . $story['title'] . '</h1>');

    // Byline
    if (!empty($story['byline'])) {
        $mpdf->WriteHTML('<div class="chapter-byline">' . $story['byline'] . '</div>');
    }

    // -----------------------------------------------------
    // Story Page (Right / Odd)
    // -----------------------------------------------------
    $mpdf->WriteHTML('<pagebreak type="NEXT-ODD" />');

    // Body
    $bodyHtml = $Parsedown->text($story['body']);

    // Fix inline images
    $bodyHtml = preg_replace_callback('/src="([^"]+)"/', function($matches) {
        $src = $matches[1];
        // Check if it's a local asset
        if (strpos($src, 'assets/images/') !== false) {
             $imageName = basename($src);
             $imageNamePng = str_replace('.jpg', '.png', $imageName);
             $originalPath = realpath(__DIR__ . '/../originals/content/assets/images/' . $imageNamePng);
             if ($originalPath && file_exists($originalPath)) {
                 return 'src="' . $originalPath . '"';
             }
        }
        return $matches[0];
    }, $bodyHtml);

    $mpdf->WriteHTML($bodyHtml);
}

$interiorOutputFile = $outputDir . '/interior.pdf';
$mpdf->Output($interiorOutputFile, \Mpdf\Output\Destination::FILE);

// Save page count for cover generation
$pageCount = $mpdf->page;
file_put_contents(__DIR__ . '/../logs/page_count.txt', $pageCount);
echo "Page count ($pageCount) saved to logs/page_count.txt\n";

echo "Interior generated at: $interiorOutputFile\n";
