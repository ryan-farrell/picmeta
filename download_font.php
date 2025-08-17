#!/usr/bin/env php
<?php
/**
 * Font Download Helper
 *
 * Downloads a free TTF font for testing the photo metadata annotator.
 * This script downloads the DejaVu Sans font from Google Fonts.
 */

echo "Font Download Helper\n";
echo "===================\n\n";

// Create fonts directory if it doesn't exist
if (!is_dir('fonts')) {
    mkdir('fonts', 0755, true);
    echo "Created fonts directory\n";
}

$fontUrl = 'https://github.com/dejavu-fonts/dejavu-fonts/raw/master/ttf/DejaVuSans.ttf';
$fontPath = 'fonts/DejaVuSans.ttf';

if (file_exists($fontPath)) {
    echo "Font already exists: $fontPath\n";
    echo "Skipping download.\n";
    exit(0);
}

echo "Downloading DejaVu Sans font...\n";

// Download the font
$context = stream_context_create([
    'http' => [
        'timeout' => 30,
        'user_agent' => 'Mozilla/5.0 (compatible; Photo Metadata Annotator)'
    ]
]);

$fontData = @file_get_contents($fontUrl, false, $context);

if ($fontData === false) {
    echo "Error: Could not download font from $fontUrl\n";
    echo "Please download a TTF font manually and place it in the fonts/ directory.\n";
    echo "You can find free fonts at:\n";
    echo "- Google Fonts: https://fonts.google.com/\n";
    echo "- DejaVu Fonts: https://dejavu-fonts.github.io/\n";
    exit(1);
}

if (file_put_contents($fontPath, $fontData) === false) {
    echo "Error: Could not save font to $fontPath\n";
    echo "Check write permissions for the fonts directory.\n";
    exit(1);
}

echo "Successfully downloaded font: $fontPath\n";
echo "Font size: " . number_format(strlen($fontData)) . " bytes\n\n";

echo "You can now use the font with the annotator:\n";
echo "php annotate_images.php --font=./fonts/DejaVuSans.ttf\n";
