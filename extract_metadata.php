#!/usr/bin/env php
<?php
/**
 * EXIF Metadata Extractor - Batch Version
 *
 * Extracts all available EXIF metadata from images in input directory
 * and saves detailed analysis to report directory with same filenames.
 */

if (php_sapi_name() !== 'cli') {
    echo "This script must be run from the command line.\n";
    exit(1);
}

// Check if EXIF extension is available
if (!extension_loaded('exif')) {
    echo "Error: EXIF extension is not available.\n";
    exit(1);
}

// Process all images in input directory
$inputDir = 'input';
$outputDir = 'report';

// Create report directory if it doesn't exist
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
    echo "Created report directory: $outputDir\n";
}

/**
 * Function to recursively format EXIF data
 */
function formatExifData($data, $indent = 0)
{
    $output = '';
    $indentStr = str_repeat('  ', $indent);

    foreach ($data as $key => $value) {
        if (is_array($value)) {
            $output .= $indentStr . "$key:\n";
            $output .= formatExifData($value, $indent + 1);
        } else {
            $output .= $indentStr . "$key: $value\n";
        }
    }

    return $output;
}

// Get all image files
$imageFiles = glob($inputDir . '/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);

if (empty($imageFiles)) {
    echo "No image files found in $inputDir directory.\n";
    exit(1);
}

echo "Found " . count($imageFiles) . " image(s) to process.\n\n";

foreach ($imageFiles as $inputFile) {
    $outputFile = $outputDir . '/' . pathinfo($inputFile, PATHINFO_FILENAME) . '.txt';

    echo "Processing: " . basename($inputFile) . "\n";
    echo "Output: $outputFile\n";

    // Read all EXIF data
    $exif = exif_read_data($inputFile, 'ANY_TAG', true);

    if (!$exif) {
        echo "No EXIF data found in the image.\n";
        file_put_contents($outputFile, "No EXIF data found in: $inputFile\n");
        echo "\n";
        continue;
    }

    // Get basic image info
    $imageInfo = getimagesize($inputFile);
    $fileSize = filesize($inputFile);

    // Start building the output
    $output = "EXIF Metadata Analysis\n";
    $output .= "====================\n\n";
    $output .= "File: $inputFile\n";
    $output .= "File Size: " . number_format($fileSize) . " bytes\n";
    $output .= "Dimensions: " . $imageInfo[0] . " x " . $imageInfo[1] . " pixels\n";
    $output .= "Image Type: " . $imageInfo[2] . " (" . image_type_to_mime_type($imageInfo[2]) . ")\n\n";

    $output .= "ALL EXIF DATA:\n";
    $output .= "==============\n";

    $output .= formatExifData($exif);

    $output .= "\n\nKEY METADATA EXTRACTED:\n";
    $output .= "======================\n";

    // Extract key metadata fields
    $keyFields = [
        'IFD0' => ['Make', 'Model', 'DateTime', 'Orientation', 'XResolution', 'YResolution', 'ResolutionUnit'],
        'EXIF' => [
            'DateTimeOriginal', 'DateTimeDigitized', 'ExposureTime', 'FNumber', 'ISOSpeedRatings',
            'FocalLength', 'FocalLengthIn35mmFilm', 'Flash', 'WhiteBalance', 'ExposureMode',
            'ExposureProgram', 'MeteringMode', 'LightSource', 'SensingMethod', 'FileSource',
            'SceneType', 'CustomRendered', 'ExposureBiasValue', 'MaxApertureValue',
            'SubjectDistance', 'DigitalZoomRatio', 'GainControl', 'Contrast', 'Saturation',
            'Sharpness', 'SubjectDistanceRange', 'UndefinedTag:0x0095', 'UndefinedTag:0x009A'
        ],
        'COMPUTED' => ['ApertureFNumber', 'Thumbnail.FileType', 'Thumbnail.MimeType'],
        'GPS' => ['GPSLatitude', 'GPSLongitude', 'GPSAltitude', 'GPSTimeStamp', 'GPSDateStamp'],
        'MAKERNOTE' => ['UndefinedTag:0x0001', 'UndefinedTag:0x0002', 'UndefinedTag:0x0003']
    ];

    foreach ($keyFields as $section => $fields) {
        if (isset($exif[$section])) {
            $output .= "\n$section:\n";
            $output .= str_repeat('-', strlen($section)) . "\n";

            foreach ($fields as $field) {
                if (isset($exif[$section][$field])) {
                    $value = $exif[$section][$field];
                    if (is_array($value)) {
                        $output .= "  $field: " . implode(', ', $value) . "\n";
                    } else {
                        $output .= "  $field: $value\n";
                    }
                }
            }
        }
    }

    $output .= "\n\nCAMERA INFORMATION:\n";
    $output .= "==================\n";

    if (isset($exif['IFD0']['Make'])) {
        $output .= "Camera Make: " . $exif['IFD0']['Make'] . "\n";
    }
    if (isset($exif['IFD0']['Model'])) {
        $output .= "Camera Model: " . $exif['IFD0']['Model'] . "\n";
    }

    $output .= "\nEXPOSURE SETTINGS:\n";
    $output .= "==================\n";

    if (isset($exif['EXIF']['ExposureTime'])) {
        $exposure = $exif['EXIF']['ExposureTime'];
        if (is_numeric($exposure)) {
            if ($exposure >= 1) {
                $output .= "Exposure Time: {$exposure}s\n";
            } else {
                $output .= "Exposure Time: 1/" . round(1 / $exposure) . "s\n";
            }
        } else {
            $output .= "Exposure Time: $exposure\n";
        }
    }

    if (isset($exif['EXIF']['FNumber'])) {
        $output .= "F-Number: f/" . $exif['EXIF']['FNumber'] . "\n";
    }

    if (isset($exif['EXIF']['ISOSpeedRatings'])) {
        $output .= "ISO: " . $exif['EXIF']['ISOSpeedRatings'] . "\n";
    }

    if (isset($exif['EXIF']['FocalLength'])) {
        $output .= "Focal Length: " . $exif['EXIF']['FocalLength'] . "mm\n";
    }

    if (isset($exif['EXIF']['FocalLengthIn35mmFilm'])) {
        $output .= "Focal Length (35mm): " . $exif['EXIF']['FocalLengthIn35mmFilm'] . "mm\n";
    }

    $output .= "\nLENS INFORMATION:\n";
    $output .= "=================\n";

    if (isset($exif['EXIF']['UndefinedTag:0x0095'])) {
        $output .= "Lens: " . $exif['EXIF']['UndefinedTag:0x0095'] . "\n";
    } elseif (isset($exif['EXIF']['UndefinedTag:0x009A'])) {
        $output .= "Lens: " . $exif['EXIF']['UndefinedTag:0x009A'] . "\n";
    }

    $output .= "\nDATE/TIME INFORMATION:\n";
    $output .= "=====================\n";

    if (isset($exif['EXIF']['DateTimeOriginal'])) {
        $output .= "Date/Time Original: " . $exif['EXIF']['DateTimeOriginal'] . "\n";
    }
    if (isset($exif['IFD0']['DateTime'])) {
        $output .= "Date/Time Modified: " . $exif['IFD0']['DateTime'] . "\n";
    }

    $output .= "\nORIENTATION:\n";
    $output .= "============\n";

    if (isset($exif['IFD0']['Orientation'])) {
        $orientation = $exif['IFD0']['Orientation'];
        $output .= "Orientation: $orientation\n";

        $orientations = [
            1 => 'Normal (0°)',
            2 => 'Mirror horizontal',
            3 => 'Rotate 180°',
            4 => 'Mirror vertical',
            5 => 'Mirror horizontal and rotate 270° CW',
            6 => 'Rotate 90° CW',
            7 => 'Mirror horizontal and rotate 90° CW',
            8 => 'Rotate 270° CW'
        ];

        if (isset($orientations[$orientation])) {
            $output .= "Orientation Description: " . $orientations[$orientation] . "\n";
        }
    }

    $output .= "\nGPS INFORMATION:\n";
    $output .= "================\n";

    if (isset($exif['GPS'])) {
        foreach ($exif['GPS'] as $key => $value) {
            if (is_array($value)) {
                $output .= "$key: " . implode(', ', $value) . "\n";
            } else {
                $output .= "$key: $value\n";
            }
        }
    }

    // Save to file
    if (file_put_contents($outputFile, $output)) {
        echo "Metadata analysis saved to: $outputFile\n";
        echo "File size: " . number_format(strlen($output)) . " bytes\n";
    } else {
        echo "Error: Could not save metadata to file.\n";
        continue;
    }

    echo "\n";
}

echo "Analysis complete!\n";
