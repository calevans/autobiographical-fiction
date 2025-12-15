<?php

$files = glob(__DIR__ . '/../content/*.md');
$totalWords = 0;
$totalInlineImages = 0;
$totalStories = 0;
$storyStats = [];

foreach ($files as $file) {
    if (basename($file) === 'index.md') {
        continue;
    }

    $content = file_get_contents($file);

    // Separate frontmatter and body
    $parts = explode('---', $content);
    // parts[0] is empty, parts[1] is frontmatter, parts[2] is body (usually)
    if (count($parts) < 3) {
        continue;
    }

    $frontmatter = $parts[1];
    $body = implode('---', array_slice($parts, 2));

    // Check for frontmatter image
    $hasHeroImage = preg_match('/^image:\s*".+"/m', $frontmatter);

    // Count inline images in body
    $inlineImages = substr_count($body, '!['); // Basic markdown image check

    // Clean body for word count
    // Remove image tags
    $textBody = preg_replace('/!\[.*?\]\(.*?\)/', '', $body);
    // Remove HTML tags
    $textBody = strip_tags($textBody);
    // Remove other markdown artifacts (headers, bold, etc - rough approximation)
    $textBody = str_replace(['#', '*', '_', '`', '-', '>'], ' ', $textBody);

    $wordCount = str_word_count($textBody);

    $storyStats[] = [
        'file' => basename($file),
        'words' => $wordCount,
        'hero_image' => $hasHeroImage,
        'inline_images' => $inlineImages
    ];

    $totalWords += $wordCount;
    $totalInlineImages += $inlineImages;
    if ($hasHeroImage) {
        // We'll count hero images separately in the final calc
    }
    $totalStories++;
}

echo "Analysis of " . count($storyStats) . " stories:\n";
echo "----------------------------------------\n";
foreach ($storyStats as $stat) {
    echo "Story: {$stat['file']}\n";
    echo "  Words: {$stat['words']}\n";
    echo "  Hero Image: " . ($stat['hero_image'] ? "Yes" : "No") . "\n";
    echo "  Inline Images: {$stat['inline_images']}\n";
    echo "\n";
}

echo "----------------------------------------\n";
echo "Total Stories: $totalStories\n";
echo "Total Words: $totalWords\n";
echo "Total Inline Images: $totalInlineImages\n";

// Estimation Logic
// Mass Market Paperback: 4.25" x 6.875"
// Approx 200 words per page.

$wordsPerPage = 200;
$pagesFromText = ceil($totalWords / $wordsPerPage);

// Image assumptions
// Hero image: 1 full page per story?
$pagesFromHeroImages = $totalStories * 1;

// Inline images: 0.5 page each?
$pagesFromInlineImages = ceil($totalInlineImages * 0.5);

// Chapter starts often have whitespace.
// If we assume continuous text, the calculation is simple.
// If each story starts on a new page, we might have partial empty pages at the end of stories.
// On average, 0.5 page wasted per story.
$whitespaceWaste = ceil($totalStories * 0.5);

$totalEstimatedPages = $pagesFromText + $pagesFromHeroImages + $pagesFromInlineImages + $whitespaceWaste;

echo "\nEstimation for 4.25\" x 6.875\" book:\n";
echo "  Text Pages (@{$wordsPerPage} wpp): $pagesFromText\n";
echo "  Hero Image Pages (1 per story): $pagesFromHeroImages\n";
echo "  Inline Image Pages (0.5 per image): $pagesFromInlineImages\n";
echo "  New Chapter Whitespace (approx): $whitespaceWaste\n";
echo "  --------------------------------\n";
echo "  TOTAL ESTIMATED PAGES: $totalEstimatedPages\n";
