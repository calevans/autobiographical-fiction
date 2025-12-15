<?php

$files = glob(__DIR__ . '/../content/*.md');

foreach ($files as $file) {
    if (basename($file) === 'index.md') {
        continue;
    }

    $content = file_get_contents($file);
    if (preg_match('/original_url: "(.*?)"/', $content, $matches)) {
        $url = $matches[1];
        echo "Processing $file ($url)...\n";

        $html = @file_get_contents($url);
        if ($html === false) {
            echo "  Failed to fetch URL.\n";
            continue;
        }

        // Look for "Post date" followed by date
        // Example: Post dateApril 12, 2008
        // The HTML might have tags, so strip tags first or use a robust regex
        // Based on the fetch_webpage output, it seems to be plain text in the tool output,
        // but in raw HTML it might be inside an element.
        // Let's try to match the text pattern.

        // The tool output showed: "Post dateApril 12, 2008"
        // This suggests the HTML might be like "Post date<span...>April 12, 2008</span>" or similar,
        // and the tool stripped tags but missed the space? Or maybe there is no space.

        // Let's try to match "Post date" followed by optional tags/spaces and then the date.
        // Date format: Month DD, YYYY

        if (preg_match('/Post date\s*(?:<[^>]*>)*\s*([A-Za-z]+ \d{1,2}, \d{4})/i', $html, $dateMatches)) {
            $dateStr = $dateMatches[1];
            $dateObj = DateTime::createFromFormat('F j, Y', $dateStr);

            if ($dateObj) {
                $newDate = $dateObj->format('Y-m-d 00:00:00');
                echo "  Found date: $dateStr -> $newDate\n";

                // Update the file
                $newContent = preg_replace('/date: ".*?"/', 'date: "' . $newDate . '"', $content);
                file_put_contents($file, $newContent);
                echo "  Updated file.\n";
            } else {
                echo "  Failed to parse date: $dateStr\n";
            }
        } else {
            echo "  Could not find date in HTML.\n";
            // Fallback: try to extract from URL if HTML fails
            if (preg_match('/\/(\d{4})\/(\d{2})\/(\d{2})\//', $url, $urlMatches)) {
                $newDate = "{$urlMatches[1]}-{$urlMatches[2]}-{$urlMatches[3]} 00:00:00";
                echo "  Fallback to URL date: $newDate\n";
                 $newContent = preg_replace('/date: ".*?"/', 'date: "' . $newDate . '"', $content);
                file_put_contents($file, $newContent);
                echo "  Updated file.\n";
            }
        }
    }
}
