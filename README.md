# 📸 Photo Metadata Annotator

A self-contained PHP CLI script that processes photos in a directory, reads EXIF metadata, corrects orientation, and overlays metadata as human-readable text onto images.

## ✨ Features

-   📊 **EXIF Metadata Reading**: Extracts camera, lens, exposure, ISO, aperture, date, and orientation data
-   🔄 **Orientation Correction**: Automatically corrects image orientation based on EXIF data
-   📝 **Text Overlay**: Adds metadata as semi-transparent text overlay at the bottom of images
-   🎨 **Font Support**: Uses TTF fonts with wrapping when available, falls back to GD built-in fonts
-   🖼️ **Multiple Formats**: Supports JPEG, PNG, GIF, and WebP input/output
-   ⚡ **Graceful Handling**: Skips files without EXIF data, still showing basic image info
-   📏 **Resizing**: Optional maximum width resizing while maintaining aspect ratio
-   🚀 **No Dependencies**: Uses only core PHP extensions (GD, EXIF)

## 🔧 Requirements

-   🐘 PHP 8.0 or higher
-   🖼️ GD extension (for image processing)
-   📊 EXIF extension (for metadata reading)
-   🎨 FreeType support (optional, for TTF font rendering)

## 📦 Installation

1. 📥 Download the `annotate_images.php` script
2. 🔧 Make it executable: `chmod +x annotate_images.php`
3. ✅ Ensure PHP has GD and EXIF extensions enabled

## 🚀 Usage

### 📋 Basic Usage

```bash
# Process images from input/ folder to output/ folder
php annotate_images.php

# Specify custom input and output directories
php annotate_images.php --in=./photos --out=./processed

# Use a custom TTF font
php annotate_images.php --in=./photos --out=./processed --font=./fonts/DejaVuSans.ttf

# Resize images to maximum width of 1920px
php annotate_images.php --in=./photos --out=./processed --max-width=1920
```

### ⚙️ Command Line Options

-   📁 `--in=DIR`: Input directory containing images (default: `./input`)
-   📁 `--out=DIR`: Output directory for processed images (default: `./output`)
-   🎨 `--font=FILE`: TTF font file for text rendering (optional)
-   📏 `--max-width=N`: Maximum width for resizing (optional)
-   ❓ `--help`: Show help message

### 📁 Example Directory Structure

```
project/
├── annotate_images.php
├── input/
│   ├── photo1.jpg
│   ├── photo2.png
│   └── photo3.webp
├── output/
│   ├── photo1.jpg
│   ├── photo2.png
│   └── photo3.webp
└── fonts/
    └── DejaVuSans.ttf
```

## 🔄 What the Script Does

For each image, the script:

1. 📥 **Loads the image** using appropriate GD functions
2. 📊 **Reads EXIF metadata** (camera make/model, lens, exposure settings, date, etc.)
3. 🔄 **Corrects orientation** if EXIF orientation tag is set
4. 📏 **Resizes** (optional) to maximum width while maintaining aspect ratio
5. 📝 **Creates text overlay** with metadata in a semi-transparent black box
6. 💾 **Saves the processed image** in the same format as the original

## 📊 Metadata Displayed

The script overlays the following information (when available):

-   📷 Camera make and model
-   🔍 Lens information
-   ⚙️ Exposure settings (shutter speed, aperture, ISO, focal length)
-   📅 Date and time
-   📐 Image dimensions

## 🎨 Font Support

-   ✨ **With TTF font**: Uses `imagettftext()` with word wrapping for better text rendering
-   📝 **Without TTF font**: Falls back to GD's built-in fonts using `imagestring()`

## 🖼️ Supported Formats

### 📥 Input Formats

-   🖼️ JPEG (.jpg, .jpeg)
-   🖼️ PNG (.png)
-   🖼️ GIF (.gif)
-   🖼️ WebP (.webp)

### 📤 Output Formats

-   🔄 Same as input format
-   ⭐ Maintains quality settings (JPEG: 95%, PNG: compression level 9, WebP: 95%)

## ⚡ Error Handling

The script gracefully handles:

-   ⚠️ Missing EXIF data (still processes image with basic info)
-   🚫 Unsupported file formats (skips with warning)
-   🎨 Missing font files (falls back to built-in fonts)
-   📁 Directory creation issues
-   💾 Image loading/saving errors

## 📋 Example Output

```
Photo Metadata Annotator
Input directory: ./input
Output directory: ./output
Font file: ./fonts/DejaVuSans.ttf

Processing: DSC_001.jpg
  Saved: DSC_001.jpg
Processing: DSC_002.png
  Saved: DSC_002.png
Skipping: document.pdf (unsupported format)

Processing complete!
Processed: 2 files
Errors: 0 files
```

## 🔧 Troubleshooting

### 🚨 Common Issues

1. **"Missing required PHP extensions"**

    - 🔧 Install GD and EXIF extensions for PHP
    - 🐧 On Ubuntu/Debian: `sudo apt-get install php-gd php-exif`
    - 🍎 On macOS with Homebrew: `brew install php` (includes extensions)

2. **"Cannot load image"**

    - 🔐 Check file permissions
    - ✅ Verify image format is supported
    - 🔍 Ensure image file is not corrupted

3. **"Font file not found"**

    - 📥 Download a TTF font (e.g., DejaVu Sans)
    - 🔧 Update the font path in your command
    - 📝 Script will fall back to built-in fonts

4. **"Cannot create directory"**
    - 🔐 Check write permissions for output directory
    - 💾 Ensure sufficient disk space

### 📥 Getting TTF Fonts

Download free TTF fonts from:

-   🌐 Google Fonts: https://fonts.google.com/
-   📚 DejaVu Fonts: https://dejavu-fonts.github.io/
-   💻 System fonts (on macOS: `/System/Library/Fonts/`, on Linux: `/usr/share/fonts/`)

## 📄 License

This script is provided as-is for educational and personal use. Feel free to modify and distribute as needed.
