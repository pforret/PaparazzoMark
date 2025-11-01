# Usage Guide

## Available Commands

PaparazzoMark provides three main commands:

1. `config:create` - Generate a default configuration file
2. `config:fonts` - List available fonts
3. `export:photos` - Process and watermark your photos

## Command: config:create

Create a default configuration file to customize your watermarks.

### Basic Usage

```bash
php run.php config:create
```

Creates `pmark.ini` in the current directory.

### With Custom Output Path

```bash
php run.php config:create --output=myconfig.ini
php run.php config:create -o /path/to/config.ini
```

### Example Output

```
Created config file: pmark.ini
Edit this file to customize your watermark settings.
```

The generated file includes:
- Global export settings
- Example text watermarks
- Example logo overlay
- Detailed comments explaining each option

## Command: config:fonts

List all available fonts for use in text overlays.

### Usage

```bash
php run.php config:fonts
```

### Example Output

```
Arial
Arial-Bold
Courier
Helvetica
Nunito-Bold.ttf
Nunito-Regular.ttf
Times-Roman
```

Shows:
- System fonts registered with ImageMagick
- Custom TTF fonts from `/font` directory

### Using Fonts

In your config file:

```ini
# Use system font by name
text_font="Arial"

# Use custom TTF from /font directory
text_font="Nunito-Bold.ttf"

# Use absolute path
text_font="/absolute/path/to/font.ttf"
```

## Command: export:photos

Process photos in the current directory and apply watermarks.

### Basic Usage

```bash
php run.php export:photos
```

Looks for `pmark.ini` in current directory and processes all images.

### With Custom Config

```bash
php run.php export:photos --config=myconfig.ini
php run.php export:photos -c /path/to/config.ini
```

### Dry Run Mode

Preview what will be processed without creating files:

```bash
php run.php export:photos --dry-run
```

Output:
```
PaparazzoMark - Photo Watermarking
Config: /path/to/pmark.ini
Output: /path/to/output
Images: 150 | Overlays: 2

DRY RUN - No files will be created
Operations configured:
  - Text: © 2025 Your Name (NorthWest)
  - Text: YourWebsite.com (SouthEast)
```

### Debug Mode

Keep temporary files for troubleshooting:

```bash
php run.php export:photos --debug
php run.php export:photos -d
```

Output:
```
Done! Processed: 150 | Skipped: 0 | Time: 32.5s
Debug: Temp files in .temp
```

## Typical Workflow

### 1. Prepare Your Photos

Export your photos to a working directory:

```bash
cd /path/to/event/photos
```

### 2. Create Configuration

```bash
php run.php config:create
```

### 3. Customize Config

Edit `pmark.ini`:

```ini
[_export]
contact_name="Your Name"
contact_email="your@email.com"
export_folder="../_MARKED"
export_height="1200"

[copyright]
text="© 2025 Your Name"
gravity="NorthWest"
```

### 4. Test with Dry Run

```bash
php run.php export:photos --dry-run
```

Verify the configuration looks correct.

### 5. Process Photos

```bash
php run.php export:photos
```

### 6. Check Results

Output appears in the folder specified by `export_folder`.

## Processing Behavior

### File Selection

- Processes files matching `source_format` (e.g., `jpg,JPG,png`)
- Scans current directory only (not recursive)
- Skips hidden files and directories

### Overwrite Mode

```ini
overwrite=1  # Always reprocess all images
overwrite=0  # Skip images if output exists and is newer
```

### Performance

Typical processing speeds:
- **Small images** (1-2 MB): 10-20 pics/sec
- **Medium images** (3-5 MB): 5-10 pics/sec
- **Large images** (10+ MB): 2-5 pics/sec

Speed depends on:
- Number of overlays
- Text effects (shadow/outline slower than plain)
- Image dimensions
- CPU speed
- Disk I/O

### Output

Progress bar shows:
```
50/150 [▓▓▓▓▓▓▓▓░░░░░░░░] 33%
```

Summary shows:
```
Done! Processed: 150 | Skipped: 0 | Time: 32.5s | Speed: 4.6 pics/s, 18.2 MB/s
```

## Common Use Cases

### Wedding Photography

```bash
cd ~/Photos/Wedding-Smith-2025
php run.php config:create
# Edit pmark.ini: add copyright, couple names
php run.php export:photos
```

### Event Series

```bash
# Process multiple events with same branding
cd ~/Photos/Events
for event in Event-*/; do
  cd "$event"
  cp ../master-config.ini pmark.ini
  php /path/to/PaparazzoMark/run.php export:photos
  cd ..
done
```

### Quick Social Media

```bash
# Quick watermark for social sharing
cd ~/Photos/ToShare
php run.php config:create
# Edit minimal config: just add website watermark
php run.php export:photos
```

## Next Steps

- [Configuration Reference](configuration.md) - All config options explained
- [Examples](examples/index.md) - Sample configurations for different scenarios
