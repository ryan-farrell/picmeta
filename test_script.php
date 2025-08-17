#!/usr/bin/env php
<?php
/**
 * Test script for Photo Metadata Annotator
 *
 * This script tests the functionality of annotate_images.php
 * and provides a simple way to verify everything works correctly.
 */

echo "Photo Metadata Annotator - Test Script\n";
echo "=====================================\n\n";

// Check PHP version
echo "PHP Version: " . PHP_VERSION . "\n";
if (version_compare(PHP_VERSION, '8.0.0', '<')) {
    echo "Warning: PHP 8.0+ recommended, but continuing...\n";
}

// Check required extensions
$required_extensions = ['gd', 'exif'];
$missing_extensions = [];

foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✓ $ext extension: Available\n";
    } else {
        echo "✗ $ext extension: Missing\n";
        $missing_extensions[] = $ext;
    }
}

if (!empty($missing_extensions)) {
    echo "\nError: Missing required extensions: " . implode(', ', $missing_extensions) . "\n";
    echo "Please install these extensions before running the main script.\n";
    exit(1);
}

// Check FreeType support
if (function_exists('imagettftext')) {
    echo "✓ FreeType support: Available (TTF fonts supported)\n";
} else {
    echo "⚠ FreeType support: Not available (will use GD built-in fonts)\n";
}

// Check if main script exists
if (file_exists('annotate_images.php')) {
    echo "✓ Main script: Found\n";
} else {
    echo "✗ Main script: Not found (annotate_images.php)\n";
    exit(1);
}

// Check directories
$directories = ['input', 'output', 'fonts'];
foreach ($directories as $dir) {
    if (is_dir($dir)) {
        echo "✓ Directory '$dir': Exists\n";
    } else {
        echo "⚠ Directory '$dir': Missing (will be created when needed)\n";
    }
}

// Check for sample images
$sample_files = glob('input/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
if (!empty($sample_files)) {
    echo "✓ Sample images: Found " . count($sample_files) . " files\n";
    foreach ($sample_files as $file) {
        echo "  - " . basename($file) . "\n";
    }
} else {
    echo "⚠ Sample images: No images found in input/ directory\n";
    echo "  Place some JPEG, PNG, GIF, or WebP files in the input/ directory to test.\n";
}

// Check for TTF fonts
$font_files = glob('fonts/*.ttf');
if (!empty($font_files)) {
    echo "✓ TTF fonts: Found " . count($font_files) . " files\n";
    foreach ($font_files as $file) {
        echo "  - " . basename($file) . "\n";
    }
} else {
    echo "⚠ TTF fonts: No TTF fonts found in fonts/ directory\n";
    echo "  Download a TTF font (e.g., DejaVu Sans) for better text rendering.\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Test completed successfully!\n\n";

if (empty($sample_files)) {
    echo "To test the script:\n";
    echo "1. Add some image files to the input/ directory\n";
    echo "2. Run: php annotate_images.php\n";
    echo "3. Check the output/ directory for processed images\n\n";
} else {
    echo "Ready to process images!\n";
    echo "Run: php annotate_images.php\n";
    echo "Or with custom options: php annotate_images.php --in=./input --out=./output\n\n";
}

echo "For help: php annotate_images.php --help\n";
