# PRP: Migrate Legacy Photo Watermarking to Modern Architecture

**Date**: 2025-11-01
**Feature**: Copy functionality from `php_version/` to new `src/` version
**Complexity**: High
**Estimated Implementation Time**: 4-6 hours

## Overview

Migrate the complete working photo watermarking functionality from the legacy procedural PHP implementation (`php_version/pmark.php`) to the modern object-oriented architecture using Symfony Console, with:
- INI configuration reading → `src/Config` namespace
- ImageMagick functionality → `src/Image` namespace
- Command implementation → `src/Command/ExportPhotosCommand`

## Context & Research

### Legacy Implementation Analysis

**Location**: `php_version/`

**Key Files**:
1. `pmark.php` (lines 1-240) - Main entry point with complete processing logic
2. `lib/IniFile.php` (lines 1-133) - INI file parsing with section support
3. `lib/PrepMagick.php` (lines 1-270) - ImageMagick command builder
4. `lib/Tools.php` (lines 1-240) - Utility functions (trace, cmdline, file operations)

**Legacy Flow**:
```
1. Find INI file (pmark.ini) in [cwd, parent, script_dir]
2. Parse INI with sections: [_export], [_default], [copyright], [subscript], [logo], etc.
3. Create PrepMagick instance → creates temp folder (.temp)
4. Build resize command from export_height/export_width
5. Iterate sections, build overlay commands:
   - Text overlays: overlay_text() → creates temp PNG with text effects
   - Image overlays: overlay_image() → creates temp PNG with logo/watermark
   - Borders: bordercolor + border size
6. Find source images (glob by source_format wildcard)
7. For each image:
   - Check if output exists and is newer (skip if not overwrite)
   - Execute: magick "input.jpg" [resize] [overlays] [borders] [quality] "output.jpg"
   - Track performance (pics/sec, MB/s)
8. Set EXIF metadata via IPTC tags
9. Cleanup temp files
10. Open output folder in explorer/finder
```

**INI Configuration Structure** (from `pmark.example.ini`):
```ini
[_export]                    # Global settings
prog_im_convert="magick.exe"
prog_im_identify="identify.exe"
contact_name="Tango Paparazzo"
export_folder="../_MARKED/$nowmonth/$basename.MK"
export_quality="90"
export_format="jpg"
source_format="jpg,JPG"
export_height="1200"
border_size="40"
overwrite=1

[_default]                   # Default values for text sections
text_font="Gabriola"
text_color="#FFF8"
text_size="30"

[copyright]                  # Example text overlay section
gravity="NorthWest"
text="© 2013 Tango Paparazzo"
rotate="90"

[logo]                       # Example image overlay section
gravity="SouthWest"
image="logo.png"
resize="300x300"
```

**Key Legacy Patterns**:
- `IniFile::get_value($section, $param, $default)` - Fallback to `[_default]` section, then to $default
- `PrepMagick::overlay_text()` - Returns ImageMagick command string, creates temp file
- `PrepMagick::overlay_image()` - Returns ImageMagick command string, creates temp file
- `PrepMagick::set_tags()` - Returns IPTC metadata command string
- `resolve_dir()` - Replaces variables: $basename, $nowdate, $imgyear, etc.
- `trace($msg, $type)` - Logging with levels: DEBUG, INFO, STAY, WARNING, ERROR
- Temp files tracked in array, cleaned up at end

### Modern Implementation Target

**Location**: `src/`

**Existing Structure**:
- `src/Command/ExportPhotosCommand.php` (stub)
- `src/Command/ConfigCreateCommand.php` (stub)
- `src/Command/ConfigFontsCommand.php` (implemented - lists fonts)
- `src/Config/` (empty - needs creation)
- `src/Image/` (empty - needs creation)

**Target Pattern** (from ConfigFontsCommand.php:1-38):
```php
#[AsCommand(name: 'config:fonts', description: 'List all available fonts')]
class ConfigFontsCommand extends Command {
    protected function execute(InputInterface $input, OutputInterface $output): int {
        // Implementation
        return Command::SUCCESS;
    }
}
```

### Dependencies Documentation

**1. spatie/image v3.x** (composer.json:15)
- Docs: https://spatie.be/docs/image/v3/introduction
- Basic usage: https://spatie.be/docs/image/v3/usage/basic-usage
- GitHub: https://github.com/spatie/image
- Requires PHP ^8.2
- Wraps ImageMagick/GD/Imagick with fluent API
- Example:
```php
use Spatie\Image\Image;

Image::load('input.jpg')
    ->width(500)
    ->height(300)
    ->blur(10)
    ->save('output.jpg');
```

**2. hassankhan/config v3.x** (composer.json:18)
- Docs: https://github.com/hassankhan/config
- Supports PHP, INI, XML, JSON, YAML files
- Requires PHP 7.4+
- Example:
```php
use Noodlehaus\Config;

$config = Config::load('config.ini');
$value = $config->get('section.key', 'default');
$all = $config->all(); // Get all config as array
```

**3. miljar/php-exif v0.6.5** (composer.json:16)
- EXIF metadata reading/writing
- Already included in dependencies

**4. Symfony Console v7.1** (composer.json:17)
- Docs: https://symfony.com/doc/current/console.html
- Style Guide: https://symfony.com/doc/current/console/style.html
- Best practices for PHP 8.2:
  - Use #[AsCommand] attribute
  - Use SymfonyStyle for consistent output
  - Call parent::__construct() in constructors
  - Return Command::SUCCESS/FAILURE/INVALID

## Implementation Blueprint

### Architecture Overview

```
src/
├── Config/
│   ├── ConfigLoader.php          # Wrapper around hassankhan/config
│   ├── ConfigValidator.php       # Validate required fields
│   └── PathResolver.php          # Resolve $basename, $nowdate, etc.
├── Image/
│   ├── ImageProcessor.php        # Main processing orchestrator
│   ├── TextOverlay.php           # Text rendering with effects
│   ├── ImageOverlay.php          # Logo/watermark compositing
│   ├── MetadataWriter.php        # EXIF/IPTC tags
│   └── TempFileManager.php       # Temp file tracking/cleanup
└── Command/
    ├── ExportPhotosCommand.php   # Main export command
    ├── ConfigCreateCommand.php   # Generate default config
    └── ConfigFontsCommand.php    # (already implemented)
```

### Class Responsibilities

**ConfigLoader** (`src/Config/ConfigLoader.php`):
```php
class ConfigLoader {
    private Config $config;

    public function __construct(string $configPath) {
        // Use hassankhan/config to load INI
    }

    public function getValue(string $section, string $key, mixed $default = null): mixed {
        // Get value with fallback to [_default] section
    }

    public function getSections(): array {
        // Return all non-special sections (not starting with _)
    }

    public function getAllValues(string $section): array {
        // Get all values for section with defaults merged
    }
}
```

**PathResolver** (`src/Config/PathResolver.php`):
```php
class PathResolver {
    public function resolve(string $path, ?string $sampleImagePath = null): string {
        // Replace: $basename, $nowdate, $nowyear, $nowmonth
        // Replace: $imgdate, $imgyear, $imgmonth (requires EXIF from sample)
    }
}
```

**ImageProcessor** (`src/Image/ImageProcessor.php`):
```php
class ImageProcessor {
    private TextOverlay $textOverlay;
    private ImageOverlay $imageOverlay;
    private MetadataWriter $metadataWriter;
    private TempFileManager $tempFileManager;

    public function processImage(
        string $inputPath,
        string $outputPath,
        array $operations,  // Array of overlay configs
        array $globalConfig
    ): void {
        // 1. Load image with spatie/image
        // 2. Resize to export dimensions
        // 3. Apply text overlays (creates temp PNGs, composites)
        // 4. Apply image overlays
        // 5. Add borders if configured
        // 6. Set quality
        // 7. Save
        // 8. Write EXIF metadata
    }
}
```

**TextOverlay** (`src/Image/TextOverlay.php`):
```php
class TextOverlay {
    public function create(array $config): string {
        // Port PrepMagick::overlay_text() logic
        // Use exec() to call ImageMagick directly for text rendering
        // (spatie/image doesn't support complex text effects like shadow/outline)
        // Returns: path to temp PNG file
    }

    private function calculateShadowDistance(int $fontSize): int {
        // Port shadow calculation logic (line 72-76)
    }

    private function getContrastColor(string $color): string {
        // Port CalcShowColor logic (line 261-268)
    }
}
```

**MetadataWriter** (`src/Image/MetadataWriter.php`):
```php
class MetadataWriter {
    public function setIptcTags(string $imagePath, array $metadata): void {
        // Port PrepMagick::set_tags() logic
        // Use ImageMagick's -profile 8BIMTEXT feature
        // Metadata: contact_name, contact_email, event, tags, caption, contact_url
    }
}
```

**ExportPhotosCommand** (`src/Command/ExportPhotosCommand.php`):
```php
#[AsCommand(name: 'export:photos', description: 'Export images with watermark')]
class ExportPhotosCommand extends Command {
    protected function configure(): void {
        $this->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Path to config file', 'pmark.ini');
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be done');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        // 1. Load config using ConfigLoader
        // 2. Resolve output directory with PathResolver
        // 3. Find source images by glob
        // 4. For each image:
        //    - Check if processing needed (overwrite flag or output older than input)
        //    - Process with ImageProcessor
        //    - Update progress bar
        //    - Track performance metrics
        // 5. Display summary
        // 6. Open output folder (platform-specific)
        // 7. Cleanup temp files
    }
}
```

### Pseudocode for Main Flow

```
COMMAND EXECUTE:
1. Initialize SymfonyStyle for output
2. Load config file (search in: cwd, parent, config option)
   - If not found: error with suggestion to run config:create
3. Validate config (required fields: export_folder, source_format)
4. Resolve output directory path (create if needed)
5. Parse source_format (handle comma-separated: "jpg,JPG,png")
6. Collect source images via glob
   - If none found: error
7. Read global config: resize, quality, border, metadata
8. Collect overlay operations from config sections
   - Filter sections: not starting with _, not _default, not _export
   - For each section: determine type (text/image/border)
9. Initialize progress bar
10. Initialize performance tracking
11. FOR EACH source image:
    - Generate output path (preserve name, use export_format)
    - Check if processing needed:
      - If output doesn't exist: YES
      - If overwrite=1: YES
      - If source newer than output: YES
      - Else: SKIP
    - Process image:
      - ImageProcessor->processImage()
    - Update progress bar
    - Update performance metrics
12. Finish progress bar
13. Display summary (N images processed, X pics/sec, Y MB/s)
14. Cleanup temp files
15. Open output folder (if not dry-run)
16. Return SUCCESS
```

### Error Handling Strategy

**Configuration Errors**:
- Config file not found → Suggest `php run.php config:create`
- Invalid INI syntax → Show parse error with line number
- Missing required fields → List missing fields with examples

**ImageMagick Errors**:
- `convert` not found → Check PATH, suggest installation
- Font not found → List available fonts via `config:fonts`
- Invalid image format → Skip with warning, continue processing

**Filesystem Errors**:
- Can't create output directory → Check permissions, show full path
- Can't write output file → Check disk space, permissions
- Source image not readable → Skip with warning

**Runtime Errors**:
- Out of memory → Suggest reducing export_height or processing in smaller batches
- Temp directory full → Show temp dir path, suggest cleanup

**Logging Strategy**:
- Use SymfonyStyle->info() for INFO level (legacy trace 'INFO')
- Use SymfonyStyle->warning() for WARNING level
- Use SymfonyStyle->error() for ERROR level
- Use SymfonyStyle->section() for major steps
- Use ProgressBar for iteration progress (legacy trace 'STAY')
- Debug mode: use --verbose flag to show DEBUG level

## Implementation Tasks (In Order)

### Phase 1: Configuration Layer
1. **Create `src/Config/ConfigLoader.php`**
   - Port `IniFile` class logic
   - Use `hassankhan/config` for INI parsing
   - Implement section fallback to `[_default]`
   - Add unit tests for config loading

2. **Create `src/Config/PathResolver.php`**
   - Port `resolve_dir()` function logic
   - Support variables: $basename, $nowdate, $imgyear, etc.
   - Handle EXIF date extraction for $img* variables
   - Add unit tests for path resolution

3. **Create `src/Config/ConfigValidator.php`**
   - Validate required fields exist
   - Check ImageMagick executables are accessible
   - Validate file paths (fonts, logos)
   - Return clear error messages

### Phase 2: Image Processing Layer
4. **Create `src/Image/TempFileManager.php`**
   - Track temporary files in array
   - Create temp directory on init
   - Cleanup method to delete all temp files
   - Auto-cleanup on destruct

5. **Create `src/Image/TextOverlay.php`**
   - Port `PrepMagick::overlay_text()` logic (lines 39-124)
   - Generate temp PNG files with text effects
   - Support effects: plain, shadow, outline
   - Support: gravity, rotation, alpha, undercolor
   - Use exec() to call ImageMagick directly

6. **Create `src/Image/ImageOverlay.php`**
   - Port `PrepMagick::overlay_image()` logic (lines 126-166)
   - Support: resize, alpha, padding, gravity
   - Generate temp PNG files for overlays

7. **Create `src/Image/MetadataWriter.php`**
   - Port `PrepMagick::set_tags()` logic (lines 168-230)
   - Write IPTC tags: Credit, Contact, Object Name, Keywords, Caption
   - Use ImageMagick -profile 8BIMTEXT

8. **Create `src/Image/ImageProcessor.php`**
   - Orchestrate entire processing pipeline
   - Use spatie/image for main operations
   - Integrate TextOverlay, ImageOverlay, MetadataWriter
   - Handle errors gracefully

### Phase 3: Command Implementation
9. **Implement `src/Command/ConfigCreateCommand.php`**
   - Generate default pmark.ini from template
   - Support --output option for custom path
   - Include helpful comments in generated file
   - Copy from pmark.example.ini structure

10. **Implement `src/Command/ExportPhotosCommand.php`**
    - Port main loop from `pmark.php` (lines 96-151)
    - Use SymfonyStyle for output
    - Add progress bar for batch processing
    - Display performance metrics
    - Support --config, --dry-run, --verbose options
    - Open output folder on completion

### Phase 4: Testing & Validation
11. **Create unit tests**
    - Test ConfigLoader with sample INI
    - Test PathResolver with various patterns
    - Test each overlay class independently

12. **Create integration test**
    - End-to-end test with sample images
    - Verify output matches expected watermarked images
    - Test performance benchmarks

13. **Manual testing**
    - Test with actual photo collection
    - Verify EXIF metadata
    - Test on Windows and macOS
    - Verify font rendering

## Validation Gates

All commands must be executable and pass before PRP is considered complete.

### Code Quality
```bash
# PHP Code Formatting (Laravel Pint)
composer format

# Verify formatting
vendor/bin/pint --test
```

### Unit Tests
```bash
# Run PHPUnit tests
composer test

# With coverage (if configured)
vendor/bin/phpunit --coverage-text
```

### Functional Tests
```bash
# Test config creation
php run.php config:create --output=/tmp/test.ini
test -f /tmp/test.ini && echo "PASS: Config created" || echo "FAIL"

# Test font listing
php run.php config:fonts | grep -q "Gabriola" && echo "PASS: Fonts listed" || echo "FAIL"

# Test export with dry-run
php run.php export:photos --dry-run --config=php_version/pmark.example.ini
```

### ImageMagick Availability
```bash
# Verify ImageMagick is installed
which convert || which magick
convert -version | head -n1

# Verify identify is available
which identify
```

## Code Examples from Codebase

### INI Config Loading Pattern
**From**: `php_version/lib/IniFile.php:38-60`
```php
public function read_file($file): bool {
    if (!file_exists($file)) {
        trace("IniFile:: cannot find file [$file]");
        return false;
    }
    $this->data = parse_ini_file($file, true);  // true = parse sections
    $this->ready = true;
    return true;
}

public function get_value($section, $param, $defval = false) {
    // Check section first
    if (isset($this->data[$section][$param])) {
        return $this->data[$section][$param];
    }
    // Fallback to _default section
    if (isset($this->data[$this->defname][$param])) {
        return $this->data[$this->defname][$param];
    }
    // Fallback to default value
    return $defval ?? false;
}
```

### ImageMagick Command Building Pattern
**From**: `php_version/lib/PrepMagick.php:39-124`
```php
public function overlay_text(array $parameters): string {
    $text = $this->getvalue($parameters, 'text', '');
    $font = $this->getvalue($parameters, 'text_font', 'Courier');
    $size = $this->getvalue($parameters, 'text_size', 50);
    $style = strtolower($this->getvalue($parameters, 'text_effect', ''));

    // Create temp file path
    $tmp_txt = "$this->TmpDir/_$begin.$size." . substr(md5(serialize($parameters)), 0, 8) . '.png';
    $this->TmpFiles[] = $tmp_txt;

    // Build ImageMagick command
    $line = "-size 2000x2000 canvas:#0000 -channel RGBA -font \"$font\" -pointsize $size ";

    // Apply text effects
    if (str_contains($style, 'shadow')) {
        // Shadow effect logic...
    }

    // Execute command
    $this->RunMagick(" $line \"$tmp_txt\"");

    // Return composite command
    return "-gravity $grav \"$tmp_txt\" -composite";
}
```

### Symfony Console Pattern
**From**: `src/Command/ConfigFontsCommand.php:1-38`
```php
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'config:fonts', description: 'List all available fonts')]
class ConfigFontsCommand extends Command {
    protected function execute(InputInterface $input, OutputInterface $output): int {
        // Get fonts
        $fonts = [...];

        // Output
        foreach ($fonts as $font) {
            $output->writeln($font);
        }

        return Command::SUCCESS;
    }
}
```

### SymfonyStyle Output Pattern
```php
use Symfony\Component\Console\Style\SymfonyStyle;

protected function execute(InputInterface $input, OutputInterface $output): int {
    $io = new SymfonyStyle($input, $output);

    $io->title('Exporting Photos');
    $io->section('Configuration');
    $io->info('Output: ' . $outputDir);

    $progressBar = $io->createProgressBar(count($images));
    foreach ($images as $image) {
        // Process...
        $progressBar->advance();
    }
    $progressBar->finish();

    $io->success('Processed ' . count($images) . ' images');

    return Command::SUCCESS;
}
```

## Common Pitfalls & Gotchas

### hassankhan/config
- **Pitfall**: Config::load() returns Config object, not array
- **Solution**: Use `$config->get()` or `$config->all()` to access values
- **Pitfall**: Section syntax differs from parse_ini_file
- **Solution**: Access as `$config->get('section.key')` not `$config['section']['key']`

### spatie/image
- **Limitation**: Doesn't support complex text rendering (shadow, outline effects)
- **Solution**: Use exec() to call ImageMagick directly for text overlays, use spatie/image for main image operations
- **Pitfall**: Image::load() uses Imagick driver by default, may need to specify GD
- **Solution**: Check ImageMagick/Imagick is installed and configured

### ImageMagick via exec()
- **Pitfall**: Command path differs on Windows (magick.exe) vs Unix (convert)
- **Solution**: Make executable path configurable in INI
- **Pitfall**: Font paths may need to be absolute
- **Solution**: Resolve font paths relative to project root or use font name if in ImageMagick registry
- **Security**: Never pass unsanitized user input to exec()
- **Solution**: Validate/escape all parameters before building commands

### Performance
- **Pitfall**: Creating temp files for every overlay is I/O intensive
- **Solution**: Reuse temp files with same parameters (check MD5 hash)
- **Pitfall**: Processing large images is memory-intensive
- **Solution**: Add memory_limit check, suggest reducing export_height if needed

### Cross-Platform Compatibility
- **Pitfall**: Windows uses `explorer`, Mac uses `open` to open folders
- **Solution**: Detect OS with PHP_OS constant (already done in pmark.php:165-177)
- **Pitfall**: Path separators differ (\ vs /)
- **Solution**: Always use DIRECTORY_SEPARATOR or normalize with realpath()

## Quality Checklist

- [x] All necessary context included
  - Legacy code fully analyzed and documented
  - Modern target structure defined
  - Dependency documentation with URLs provided

- [x] Validation gates are executable by AI
  - composer format/test commands ready
  - Functional tests with bash commands
  - ImageMagick verification commands

- [x] References existing patterns
  - INI loading pattern from IniFile.php
  - Command pattern from ConfigFontsCommand.php
  - ImageMagick pattern from PrepMagick.php

- [x] Clear implementation path
  - 13 tasks in 4 phases
  - Tasks ordered by dependency
  - Each task has clear deliverable

- [x] Error handling documented
  - Error categories identified
  - Logging strategy defined
  - User-friendly error messages specified

## Confidence Score

**9/10** - High confidence for one-pass implementation

**Strengths**:
- Complete legacy code analysis with line-by-line references
- All dependencies documented with official documentation URLs
- Clear architecture with class responsibilities
- Executable validation gates
- Comprehensive error handling strategy

**Minor Risks**:
- ImageMagick command compatibility across versions (mitigation: make executable paths configurable)
- EXIF metadata format variations (mitigation: test with sample images, graceful fallbacks)
- Font availability differences (mitigation: ConfigFontsCommand already lists available fonts)

**Deduction reasoning**:
- -1 point: Need to validate ImageMagick text rendering matches legacy output exactly (may need iteration on shadow/outline algorithms)

## Additional Resources

### Documentation References
- Spatie Image: https://spatie.be/docs/image/v3/introduction
- Hassankhan Config: https://github.com/hassankhan/config
- Symfony Console: https://symfony.com/doc/current/console.html
- Symfony Style: https://symfony.com/doc/current/console/style.html
- ImageMagick CLI: https://imagemagick.org/script/command-line-processing.php
- ImageMagick Annotate: https://imagemagick.org/script/command-line-options.php#annotate
- IPTC Metadata: https://imagemagick.org/script/command-line-options.php#profile

### Example Repositories
- Spatie Image Examples: https://github.com/spatie/image/tree/main/tests
- Symfony Console Examples: https://symfony.com/doc/current/console.html#running-the-command

### Testing Images
- Use sample images from `php_version/` if available
- Create test images: 1200x800 landscape, 800x1200 portrait, 1000x1000 square
- Include test logo.png in assets/

### Legacy INI for Testing
- Use `php_version/pmark.example.ini` as test input
- Verify output matches legacy pmark.php output
- Compare EXIF metadata between legacy and modern output