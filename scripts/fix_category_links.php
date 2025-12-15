<?php

$contentDir = __DIR__ . '/../content';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($contentDir));

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'md') {
        $content = file_get_contents($file->getPathname());

        // Replace /category/slug/ with /slug.html
        $newContent = preg_replace('/\/category\/([a-zA-Z0-9-]+)\/?/', '/$1.html', $content);

        if ($content !== $newContent) {
            file_put_contents($file->getPathname(), $newContent);
            echo "Fixed links in: " . $file->getFilename() . "\n";
        }
    }
}

echo "Done.\n";
