#!/usr/bin/env php
<?php
/**
 * Photo Metadata Annotator
 *
 * A self-contained PHP CLI script that processes photos in a directory,
 * reads EXIF metadata, corrects orientation, and overlays metadata as text.
 *
 * Requirements: PHP 8+ with GD and EXIF extensions
 * Usage: php annotate_images.php --in=./input --out=./output --font=./fonts/DejaVuSans.ttf
 */

/**
 * Display usage information
 */
function showUsage()
{
    echo "Photo Metadata Annotator\n";
    echo "Usage: php " . basename(__FILE__) . " [options]\n\n";
    echo "Options:\n";
    echo "  --in=DIR      Input directory containing images (default: ./input)\n";
    echo "  --out=DIR     Output directory for processed images (default: ./output)\n";
    echo "  --font=FILE   TTF font file for text rendering (optional)\n";
    echo "  --max-width=N Maximum width for resizing (optional)\n";
    echo "  --help        Show this help message\n\n";
    echo "Example:\n";
    echo "  php " . basename(__FILE__) . " --in=./photos --out=./processed --font=./fonts/DejaVuSans.ttf\n";
}

/**
 * Parse command line arguments
 */
function parseArguments($argv)
{
    $options = [
        'in' => './input',
        'out' => './output',
        'font' => null,
        'max_width' => null
    ];

    foreach ($argv as $arg) {
        if (strpos($arg, '--') === 0) {
            $parts = explode('=', substr($arg, 2), 2);
            $key = $parts[0];
            $value = isset($parts[1]) ? $parts[1] : true;

            switch ($key) {
                case 'in':
                    $options['in'] = $value;
                    break;
                case 'out':
                    $options['out'] = $value;
                    break;
                case 'font':
                    $options['font'] = $value;
                    break;
                case 'max_width':
                    $options['max_width'] = (int)$value;
                    break;
                case 'help':
                    showUsage();
                    exit(0);
            }
        }
    }

    return $options;
}

/**
 * Check if required PHP extensions are available
 */
function checkExtensions()
{
    $required = ['gd', 'exif'];
    $missing = [];

    foreach ($required as $ext) {
        if (!extension_loaded($ext)) {
            $missing[] = $ext;
        }
    }

    if (!empty($missing)) {
        echo "Error: Missing required PHP extensions: " . implode(', ', $missing) . "\n";
        echo "Please install these extensions and try again.\n";
        exit(1);
    }
}

/**
 * Create directory if it doesn't exist
 */
function ensureDirectory($path)
{
    if (!is_dir($path)) {
        if (!mkdir($path, 0755, true)) {
            echo "Error: Cannot create directory: $path\n";
            exit(1);
        }
    }
}

/**
 * Get supported image extensions
 */
function getSupportedExtensions()
{
    return ['jpg', 'jpeg', 'png', 'gif', 'webp'];
}

/**
 * Check if file is a supported image
 */
function isSupportedImage($filename)
{
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, getSupportedExtensions());
}

/**
 * Load image from file
 */
function loadImage($filepath)
{
    $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));

    switch ($ext) {
        case 'jpg':
        case 'jpeg':
            $image = imagecreatefromjpeg($filepath);
            break;
        case 'png':
            $image = imagecreatefrompng($filepath);
            break;
        case 'gif':
            $image = imagecreatefromgif($filepath);
            break;
        case 'webp':
            $image = imagecreatefromwebp($filepath);
            break;
        default:
            return false;
    }

    if (!$image) {
        return false;
    }

    return [
        'resource' => $image,
        'format' => $ext,
        'width' => imagesx($image),
        'height' => imagesy($image)
    ];
}

/**
 * Save image to file
 */
function saveImage($imageData, $filepath)
{
    $image = $imageData['resource'];
    $format = $imageData['format'];

    // Ensure output directory exists
    $dir = dirname($filepath);
    ensureDirectory($dir);

    switch ($format) {
        case 'jpg':
        case 'jpeg':
            return imagejpeg($image, $filepath, 95);
        case 'png':
            return imagepng($image, $filepath, 9);
        case 'gif':
            return imagegif($image, $filepath);
        case 'webp':
            return imagewebp($image, $filepath, 95);
        default:
            return false;
    }
}

/**
 * Read EXIF data safely
 */
function readExifData($filepath)
{
    if (!function_exists('exif_read_data')) {
        return [];
    }

    $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg'])) {
        return []; // EXIF only supported in JPEG
    }

    $exif = @exif_read_data($filepath, 'ANY_TAG', true);
    if (!$exif) {
        return [];
    }

    $data = [];

    // Camera information
    if (isset($exif['IFD0']['Make'])) {
        $data['camera_make'] = $exif['IFD0']['Make'];
    }
    if (isset($exif['IFD0']['Model'])) {
        $data['camera_model'] = $exif['IFD0']['Model'];
    }

    // Lens information
    if (isset($exif['EXIF']['UndefinedTag:0x0095'])) {
        $data['lens'] = $exif['EXIF']['UndefinedTag:0x0095'];
    } elseif (isset($exif['EXIF']['UndefinedTag:0x009A'])) {
        $data['lens'] = $exif['EXIF']['UndefinedTag:0x009A'];
    }

    // Exposure settings
    if (isset($exif['EXIF']['ExposureTime'])) {
        $data['exposure'] = $exif['EXIF']['ExposureTime'];
    }
    if (isset($exif['EXIF']['ISOSpeedRatings'])) {
        $data['iso'] = $exif['EXIF']['ISOSpeedRatings'];
    }
    if (isset($exif['EXIF']['COMPUTED']['ApertureFNumber'])) {
        $data['aperture'] = $exif['EXIF']['COMPUTED']['ApertureFNumber'];
    } elseif (isset($exif['EXIF']['FNumber'])) {
        $data['aperture'] = 'f/' . $exif['EXIF']['FNumber'];
    }

    // Date - only use original photo date, ignore file creation/modified dates
    if (isset($exif['EXIF']['DateTimeOriginal'])) {
        $data['date'] = $exif['EXIF']['DateTimeOriginal'];
        $data['date_type'] = 'original';
    }

    // GPS coordinates
    if (isset($exif['GPS']['GPSLatitude']) && isset($exif['GPS']['GPSLongitude'])) {
        $data['gps_latitude'] = $exif['GPS']['GPSLatitude'];
        $data['gps_longitude'] = $exif['GPS']['GPSLongitude'];
        $data['gps_lat_ref'] = $exif['GPS']['GPSLatitudeRef'] ?? 'N';
        $data['gps_lon_ref'] = $exif['GPS']['GPSLongitudeRef'] ?? 'E';
    }

    // Orientation
    if (isset($exif['IFD0']['Orientation'])) {
        $data['orientation'] = $exif['IFD0']['Orientation'];
    }

        // Focal length
    if (isset($exif['EXIF']['FocalLength'])) {
        $data['focal_length'] = $exif['EXIF']['FocalLength'];
    }

    // Resolution information
    if (isset($exif['IFD0']['XResolution'])) {
        $data['x_resolution'] = $exif['IFD0']['XResolution'];
    }
    if (isset($exif['IFD0']['YResolution'])) {
        $data['y_resolution'] = $exif['IFD0']['YResolution'];
    }

    return $data;
}

/**
 * Correct image orientation based on EXIF data
 */
function correctOrientation($imageData, $orientation)
{
    $image = $imageData['resource'];

    switch ($orientation) {
        case 3:
            $image = imagerotate($image, 180, 0);
            break;
        case 6:
            $image = imagerotate($image, -90, 0);
            break;
        case 8:
            $image = imagerotate($image, 90, 0);
            break;
    }

    return [
        'resource' => $image,
        'format' => $imageData['format'],
        'width' => imagesx($image),
        'height' => imagesy($image)
    ];
}

/**
 * Resize image to maximum width while maintaining aspect ratio
 */
function resizeImage($imageData, $maxWidth)
{
    if ($imageData['width'] <= $maxWidth) {
        return $imageData;
    }

    $ratio = $maxWidth / $imageData['width'];
    $newWidth = $maxWidth;
    $newHeight = (int)($imageData['height'] * $ratio);

    $newImage = imagecreatetruecolor($newWidth, $newHeight);

    // Preserve transparency for PNG and GIF
    if (in_array($imageData['format'], ['png', 'gif'])) {
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
        imagefill($newImage, 0, 0, $transparent);
    }

    imagecopyresampled(
        $newImage,
        $imageData['resource'],
        0,
        0,
        0,
        0,
        $newWidth,
        $newHeight,
        $imageData['width'],
        $imageData['height']
    );

    return [
        'resource' => $newImage,
        'format' => $imageData['format'],
        'width' => $newWidth,
        'height' => $newHeight
    ];
}

/**
 * Format exposure time for display
 */
function formatExposure($exposure)
{
    if (is_numeric($exposure)) {
        if ($exposure >= 1) {
            return $exposure . 's';
        } else {
            return '1/' . round(1 / $exposure) . 's';
        }
    }
    return $exposure;
}

/**
 * Format date for display (UK format)
 */
function formatDate($dateString)
{
    if (!$dateString) {
        return '';
    }

    $date = DateTime::createFromFormat('Y:m:d H:i:s', $dateString);
    if ($date) {
        // UK format: dd/MM/yyyy HH:ii:ss
        return $date->format('d/m/Y H:i:s');
    }
    return $dateString;
}

/**
 * Format file size for display
 */
function formatFileSize($bytes)
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 1) . ' ' . $units[$pow];
}

/**
 * Format GPS coordinates for display
 */
function formatGPS($lat, $lon, $latRef = 'N', $lonRef = 'E')
{
    if (!is_array($lat) || !is_array($lon)) {
        return '';
    }

    // Convert GPS coordinates from degrees/minutes/seconds to decimal
    $latDecimal = $lat[0] + ($lat[1] / 60) + ($lat[2] / 3600);
    $lonDecimal = $lon[0] + ($lon[1] / 60) + ($lon[2] / 3600);

    // Apply hemisphere
    if ($latRef === 'S') {
        $latDecimal = -$latDecimal;
    }
    if ($lonRef === 'W') {
        $lonDecimal = -$lonDecimal;
    }

    return sprintf('%.6f, %.6f', $latDecimal, $lonDecimal);
}

/**
 * Create text overlay on image
 */
function overlayMetadata($imageData, $exifData, $fontPath = null, $inputPath = null)
{
    $image = $imageData['resource'];
    $width = $imageData['width'];
    $height = $imageData['height'];

        // Prepare metadata text - ONLY the required fields
    $lines = [];

    // 1. Taken: (UK time date dd/MM/yyyy HH:ii:ss) - only if original photo date exists
    if (!empty($exifData['date']) && $exifData['date_type'] === 'original') {
        $lines[] = 'Taken: ' . formatDate($exifData['date']);
    } else {
        $lines[] = 'Taken:';
    }

    // 2. Dimensions with size (always available)
    $fileSize = filesize($inputPath);
    $lines[] = 'Dimensions: ' . $width . ' x ' . $height . ' px (' . formatFileSize($fileSize) . ')';

    // 3. File info (always available)
    $lines[] = 'File: ' . basename($inputPath);

    // 4. Resolution (always available from EXIF or default)
    $xRes = $exifData['x_resolution'] ?? '72/1';
    $yRes = $exifData['y_resolution'] ?? '72/1';
    $lines[] = 'Resolution: ' . $xRes . ' x ' . $yRes . ' DPI';

    // 5. Lat/Lng (GPS coordinates)
    if (!empty($exifData['gps_latitude']) && !empty($exifData['gps_longitude'])) {
        $gps = formatGPS(
            $exifData['gps_latitude'],
            $exifData['gps_longitude'],
            $exifData['gps_lat_ref'] ?? 'N',
            $exifData['gps_lon_ref'] ?? 'E'
        );
        if ($gps) {
            $lines[] = 'Lat/Lng: ' . $gps;
        } else {
            $lines[] = 'Lat/Lng:';
        }
    } else {
        $lines[] = 'Lat/Lng:';
    }

    // Calculate overlay dimensions
    $padding = 10;
    $lineHeight = 20;
    $overlayHeight = count($lines) * $lineHeight + $padding * 2;
    $overlayWidth = $width;

    // Create semi-transparent overlay
    $overlay = imagecreatetruecolor($overlayWidth, $overlayHeight);
    $black = imagecolorallocate($overlay, 0, 0, 0);
    $transparent = imagecolorallocatealpha($overlay, 0, 0, 0, 127);

    // Fill with semi-transparent black
    imagefill($overlay, 0, 0, $transparent);
    imagecolortransparent($overlay, $transparent);

    // Add semi-transparent black background
    $semiBlack = imagecolorallocatealpha($overlay, 0, 0, 0, 100);
    imagefilledrectangle($overlay, 0, 0, $overlayWidth - 1, $overlayHeight - 1, $semiBlack);

    // Draw text
    $white = imagecolorallocate($overlay, 255, 255, 255);
    $y = $padding;

    foreach ($lines as $line) {
        if ($fontPath && function_exists('imagettftext') && file_exists($fontPath)) {
            // Use TTF font with wrapping
            $fontSize = 12;
            $maxWidth = $overlayWidth - $padding * 2;

            // Simple word wrapping
            $words = explode(' ', $line);
            $currentLine = '';
            $lineY = $y;

            foreach ($words as $word) {
                $testLine = $currentLine . ($currentLine ? ' ' : '') . $word;
                $bbox = imagettfbbox($fontSize, 0, $fontPath, $testLine);
                $textWidth = $bbox[2] - $bbox[0];

                if ($textWidth > $maxWidth && $currentLine) {
                    imagettftext($overlay, $fontSize, 0, $padding, $lineY, $white, $fontPath, $currentLine);
                    $currentLine = $word;
                    $lineY += $lineHeight;
                } else {
                    $currentLine = $testLine;
                }
            }

            if ($currentLine) {
                imagettftext($overlay, $fontSize, 0, $padding, $lineY, $white, $fontPath, $currentLine);
                $y = $lineY + $lineHeight;
            }
        } else {
            // Use GD built-in font
            imagestring($overlay, 3, $padding, $y, $line, $white);
            $y += $lineHeight;
        }
    }

    // Create new image with overlay
    $newImage = imagecreatetruecolor($width, $height + $overlayHeight);

    // Preserve transparency for PNG and GIF
    if (in_array($imageData['format'], ['png', 'gif'])) {
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
        imagefill($newImage, 0, 0, $transparent);
    }

    // Copy original image
    imagecopy($newImage, $image, 0, 0, 0, 0, $width, $height);

    // Copy overlay
    imagecopy($newImage, $overlay, 0, $height, 0, 0, $overlayWidth, $overlayHeight);

    return [
        'resource' => $newImage,
        'format' => $imageData['format'],
        'width' => $width,
        'height' => $height + $overlayHeight
    ];
}

/**
 * Process a single image file
 */
function processImage($inputPath, $outputPath, $fontPath = null, $maxWidth = null)
{
    echo "Processing: " . basename($inputPath) . "\n";

    // Load image
    $imageData = loadImage($inputPath);
    if (!$imageData) {
        echo "  Error: Cannot load image\n";
        return false;
    }

    // Read EXIF data
    $exifData = readExifData($inputPath);

    // Correct orientation if needed
    if (!empty($exifData['orientation']) && $exifData['orientation'] != 1) {
        $imageData = correctOrientation($imageData, $exifData['orientation']);
    }

    // Resize if needed
    if ($maxWidth) {
        $imageData = resizeImage($imageData, $maxWidth);
    }

    // Add metadata overlay
    $imageData = overlayMetadata($imageData, $exifData, $fontPath, $inputPath);

    // Save processed image
    if (saveImage($imageData, $outputPath)) {
        echo "  Saved: " . basename($outputPath) . "\n";
        return true;
    } else {
        echo "  Error: Cannot save image\n";
        return false;
    }
}

/**
 * Main processing function
 */
function processDirectory($inputDir, $outputDir, $fontPath = null, $maxWidth = null)
{
    if (!is_dir($inputDir)) {
        echo "Error: Input directory does not exist: $inputDir\n";
        exit(1);
    }

    ensureDirectory($outputDir);

    $files = scandir($inputDir);
    $supportedExtensions = getSupportedExtensions();
    $processed = 0;
    $errors = 0;

    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }

        $inputPath = $inputDir . '/' . $file;
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        if (!in_array($ext, $supportedExtensions)) {
            echo "Skipping: $file (unsupported format)\n";
            continue;
        }

        $outputPath = $outputDir . '/' . $file;

        if (processImage($inputPath, $outputPath, $fontPath, $maxWidth)) {
            $processed++;
        } else {
            $errors++;
        }
    }

    echo "\nProcessing complete!\n";
    echo "Processed: $processed files\n";
    echo "Errors: $errors files\n";
}

/**
 * Main execution function
 */
function main()
{
    // Main execution check
    if (php_sapi_name() !== 'cli') {
        echo "This script must be run from the command line.\n";
        exit(1);
    }

    // Error reporting for debugging
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    // Check extensions
    checkExtensions();

    // Parse arguments
    $options = parseArguments($GLOBALS['argv']);

    // Validate font file if provided
    if ($options['font'] && !file_exists($options['font'])) {
        echo "Warning: Font file not found: {$options['font']}\n";
        echo "Will use GD built-in fonts instead.\n";
        $options['font'] = null;
    }

    echo "Photo Metadata Annotator\n";
    echo "Input directory: {$options['in']}\n";
    echo "Output directory: {$options['out']}\n";
    if ($options['font']) {
        echo "Font file: {$options['font']}\n";
    }
    if ($options['max_width']) {
        echo "Max width: {$options['max_width']}px\n";
    }
    echo "\n";

    // Process images
    processDirectory($options['in'], $options['out'], $options['font'], $options['max_width']);
}

// Execute main function only when run directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'] ?? '')) {
    main();
}
