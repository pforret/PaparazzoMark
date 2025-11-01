# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

PaparazzoMark is a PHP photo watermarking tool that adds photographer credits and custom text overlays to images using ImageMagick. The project is transitioning from a legacy procedural PHP implementation (`php_version/`) to a modern OOP architecture using Symfony Console components.

## Development Commands

### Install Dependencies
```bash
composer install
```

### Code Formatting
```bash
composer format      # or: vendor/bin/pint
```

### Run Tests
```bash
composer test        # or: vendor/bin/phpunit
```

### Run Application
```bash
php run.php          # Interactive CLI - shows available commands
php run.php config:create   # Create default config file
php run.php config:fonts    # List available fonts
php run.php export:photos   # Export watermarked photos
```

## Architecture

### Dual Implementation Structure

The codebase contains two parallel implementations:

**Legacy (`php_version/`)**: Procedural PHP script (`pmark.php`) that:
- Reads configuration from INI files (`pmark.ini`)
- Uses utility classes: `IniFile`, `PrepMagick`, `Tools`
- Directly executes ImageMagick commands via shell
- Processes images in batches with real-time progress output

**Modern (`src/`)**: Symfony Console application with commands:
- `ConfigCreateCommand`: Generate default configuration
- `ConfigFontsCommand`: Display available fonts from `/font` directory
- `ExportPhotosCommand`: Main export functionality (currently stub)

The modern implementation is incomplete - the legacy `php_version/pmark.php` contains the complete working logic that needs to be refactored into the Symfony commands.

### Image Processing Flow

1. Read configuration (watermark text, position, fonts, styles)
2. Find source images by wildcard pattern
3. For each image:
   - Resize to export dimensions
   - Apply text overlays (with effects: shadow, outline, glow)
   - Apply image overlays (logos)
   - Add borders if configured
   - Set EXIF metadata tags
   - Save to output directory
4. Display performance metrics (pics/sec, MB/s)

### Key Dependencies

- **spatie/image**: High-level ImageMagick wrapper (modern approach)
- **miljar/php-exif**: EXIF data reading/writing
- **symfony/console**: CLI framework for commands
- **symfony/dotenv**: Environment configuration
- **hassankhan/config**: Configuration file handling

### ImageMagick Integration

The `PrepMagick` class (php_version/lib/PrepMagick.php) builds ImageMagick command strings:
- `overlay_text()`: Text rendering with effects (shadow/outline/glow)
- `overlay_image()`: Logo/watermark compositing
- `set_tags()`: EXIF metadata injection
- `RunMagick()`: Shell command execution wrapper

Text effects use multi-pass rendering:
- Shadow: Offset dark text + sharp color text
- Outline: 4-direction offset dark text + sharp color text
- Glow: Gaussian blur on color text + sharp color text

### Configuration System

Legacy uses INI format with sections:
- `[_export]`: Global settings (dimensions, quality, paths)
- Named sections: Individual text/image overlays with positioning

Modern implementation will use one of:
- YAML/JSON via `hassankhan/config`
- .env files via `symfony/dotenv`

### Font Management

Custom fonts stored in `/font` directory (TTF files). The `ConfigFontsCommand` scans this directory to show available typefaces.

## When Migrating Legacy to Modern

1. Port INI configuration parsing to `hassankhan/config`
2. Migrate `PrepMagick` class to use `spatie/image` API instead of raw shell commands
3. Implement batch processing logic in `ExportPhotosCommand::execute()`
4. Preserve performance monitoring (pics/sec, MB/s metrics)
5. Keep temporary file cleanup logic from `PrepMagick::Cleanup()`
6. Maintain cross-platform path handling (Windows/Mac)

## System Requirements

- PHP >= 8.2
- ImageMagick installed (`convert` and `identify` executables in PATH)
- Write permissions for output directories and temp folders