<?php
$base = __DIR__ . '/../originals/content/assets/images/';
$file = 'age-17-the-surprise-party.png';
$path = $base . $file;
echo "Path: $path\n";
if (file_exists($path)) {
    echo "Exists!\n";
} else {
    echo "Does not exist.\n";
}
echo "Realpath: " . realpath($path) . "\n";
