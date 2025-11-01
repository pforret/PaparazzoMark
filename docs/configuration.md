# Configuration Reference

Complete reference for `pmark.ini` configuration file.

## File Structure

```ini
[_export]           # Global settings (required)
[_default]          # Default values for overlays (optional)
[copyright]         # Text overlay section (custom name)
[logo]              # Image overlay section (custom name)
```

## Global Settings: [_export]

### ImageMagick Executables

```ini
prog_im_convert="convert"
prog_im_identify="identify"
```

- **convert**: ImageMagick executable for image processing
- **identify**: ImageMagick executable for reading EXIF
- Use `"magick"` for ImageMagick 7+
- Use full path if not in PATH: `"/usr/local/bin/convert"`

### Source Files

```ini
source_format="jpg,JPG"
```

- Comma-separated list of file extensions to process
- Case-sensitive
- Examples: `"jpg"`, `"jpg,JPG,png,PNG"`, `"jpeg,JPEG"`

### Export Settings

```ini
export_folder="../_MARKED/$nowmonth/$basename.MK"
```

Output directory for watermarked images. Supports variables:

| Variable | Description | Example |
|----------|-------------|---------|
| `$basename` | Current folder name | `"Wedding-Smith"` |
| `$nowyear` | Current year | `"2025"` |
| `$nowmonth` | Current year-month | `"2025-01"` |
| `$nowdate` | Current date | `"2025-01-15"` |
| `$imgyear` | Image year from EXIF | `"2024"` |
| `$imgmonth` | Image year-month from EXIF | `"2024-12"` |
| `$imgdate` | Image date from EXIF | `"2024-12-25"` |

```ini
export_format="jpg"
```

- Output image format: `jpg`, `png`, `gif`, `webp`
- Recommended: `jpg` for photos

```ini
export_quality="90"
```

- JPEG quality: 1-100
- **90-95**: Web publishing (good balance)
- **99**: Print quality (larger files)
- **85**: Smaller files, slight quality loss

### Image Dimensions

```ini
export_height="1200"
export_width=""
```

- **export_height**: Target height in pixels
- **export_width**: Target width (leave empty for auto-calculation)
- Aspect ratio is always preserved
- One dimension can be omitted for automatic calculation

### Processing Options

```ini
overwrite=1
```

- **1**: Always reprocess all images
- **0**: Skip if output exists and is newer than source

### Border (Optional)

```ini
border_size="40"
border_color="#000F"
```

- **border_size**: Border width in pixels (applies to all images)
- **border_color**: Border color in hex format
  - Format: `#RRGGBBAA` where AA is alpha (transparency)
  - Examples: `#000F` (black, almost opaque), `#FFFF` (white, opaque)

### EXIF Metadata

```ini
contact_name="Your Name"
contact_email="your@email.com"
contact_url="https://yourwebsite.com"
event="Event Name"
caption="Photo description"
tags="tag1 tag2 tag3"
```

Embedded in EXIF/IPTC metadata:
- **contact_name**: Photographer name → Credit field
- **contact_email**: Email → Contact field
- **contact_url**: Website → Copyright + By-line fields
- **event**: Event name → Object Name field
- **caption**: Description → Caption field
- **tags**: Space-separated keywords → Keyword fields

## Default Values: [_default]

Values in this section are used as defaults for all overlay sections.

```ini
[_default]
text_font="Arial"
text_color="#FFF8"
text_size="30"
padding="10"
```

Any text overlay section that doesn't specify these values will inherit them from `[_default]`.

## Text Overlay Sections

Create any number of text overlay sections with custom names.

### Basic Example

```ini
[copyright]
text="© 2025 Your Name"
gravity="NorthWest"
```

### All Options

```ini
[watermark]
text="Your Text Here"
gravity="South"
text_font="Arial"
text_size="40"
text_color="#FFF8"
text_effect="shadow"
undercolor="#0008"
padding="20"
alpha="90"
rotation="0"
```

### Text Parameters

#### text (required)

```ini
text="© 2025 Your Name"
```

- The text to display
- Supports HTML entities: `&copy;`, `&reg;`, etc.
- Multi-word text in quotes

#### gravity (required)

```ini
gravity="NorthWest"
```

Position on image:

| Value | Position |
|-------|----------|
| `NorthWest` | Top-left corner |
| `North` | Top-center |
| `NorthEast` | Top-right corner |
| `West` | Middle-left |
| `Center` | Center |
| `East` | Middle-right |
| `SouthWest` | Bottom-left corner |
| `South` | Bottom-center |
| `SouthEast` | Bottom-right corner |

#### text_font

```ini
text_font="Arial"              # System font
text_font="Nunito-Bold.ttf"    # Custom TTF from /font directory
text_font="/path/to/font.ttf"  # Absolute path
```

Find available fonts: `php run.php config:fonts`

#### text_size

```ini
text_size="40"
```

- Font size in points
- Typical range: 20-100
- Smaller for watermarks, larger for copyright notices

#### text_color

```ini
text_color="#FFF8"
```

- Hex color format: `#RRGGBBAA`
- Last two digits (AA) = alpha/transparency
  - `FF` = fully opaque
  - `88` = semi-transparent
  - `00` = fully transparent
- Common colors:
  - `#FFFF` = white opaque
  - `#FFF8` = white semi-transparent
  - `#000F` = black almost opaque
  - `#000` = black opaque

#### text_effect

```ini
text_effect="shadow"
```

Three styles:

- **`"shadow"`**: Text with drop shadow (offset bottom-right)
- **`"outline"`**: Text with outline on all sides
- **`""`** (empty/omit): Plain text, no effects

Shadow/outline color is automatically calculated as contrast to text_color.

#### undercolor

```ini
undercolor="#0008"
```

- Background color behind text
- Improves readability on complex backgrounds
- Use semi-transparent colors: `#0008` (black), `#FFF8` (white)

#### padding

```ini
padding="20"
```

- Padding around text in pixels
- Creates space between text and edge/other elements

#### alpha

```ini
alpha="80"
```

- Overall transparency: 0-100
- **100**: Fully opaque
- **80**: Slightly transparent
- **50**: Half transparent
- **0**: Fully transparent (invisible)

#### rotation

```ini
rotation="90"
```

- Rotation angle in degrees
- **0**: No rotation
- **90**: Rotate 90° clockwise
- **-90**: Rotate 90° counter-clockwise
- **45**: Diagonal

### Text Effect Examples

#### Subtle Corner Copyright

```ini
[copyright]
text="© 2025 Photography Co."
gravity="SouthEast"
text_size="20"
text_color="#FFF8"
padding="15"
alpha="80"
```

#### Bold Center Watermark

```ini
[watermark]
text="SAMPLE - NOT FOR DISTRIBUTION"
gravity="Center"
text_font="Arial-Bold"
text_size="60"
text_color="#FFFFFF40"
text_effect="outline"
rotation="45"
```

#### Website Credit with Shadow

```ini
[website]
text="www.yourwebsite.com"
gravity="South"
text_size="28"
text_color="#FFF"
text_effect="shadow"
padding="25"
```

## Image Overlay Sections

Add logo watermarks or sponsor images.

### Basic Example

```ini
[logo]
image="logo.png"
gravity="SouthWest"
```

### All Options

```ini
[sponsor]
image="/path/to/logo.png"
gravity="NorthEast"
resize="300x300"
padding="30"
alpha="90"
background="#0000"
```

### Image Parameters

#### image (required)

```ini
image="logo.png"              # Relative to project root
image="/absolute/path/logo.png"  # Absolute path
```

- Path to logo/image file
- Supports: PNG (with transparency), JPG, GIF
- PNG recommended for logos (transparency support)

#### gravity (required)

```ini
gravity="SouthWest"
```

Same positions as text overlays (see above).

#### resize

```ini
resize="300x300"
resize="200"
resize="x150"
```

- Resize logo before compositing
- `"WIDTHxHEIGHT"`: Max dimensions (preserves aspect ratio)
- `"WIDTH"`: Set width, auto-height
- `"xHEIGHT"`: Set height, auto-width

#### padding

```ini
padding="30"
```

- Space between logo and image edge in pixels

#### alpha

```ini
alpha="90"
```

- Logo transparency: 0-100
- **100**: Fully opaque
- **80-90**: Slightly transparent watermark
- **50**: Semi-transparent

#### background

```ini
background="#0000"
```

- Background color behind logo
- Usually transparent: `#0000`

### Image Overlay Examples

#### Corner Logo

```ini
[logo]
image="assets/logo.png"
gravity="SouthWest"
resize="200x200"
padding="30"
alpha="95"
```

#### Sponsor Watermark

```ini
[sponsor]
image="/path/to/sponsor.png"
gravity="NorthEast"
resize="250"
padding="20"
alpha="85"
```

## Complete Configuration Example

```ini
[_export]
# ImageMagick
prog_im_convert="convert"
prog_im_identify="identify"

# Source
source_format="jpg,JPG"

# Output
export_folder="../_MARKED/$nowmonth/$basename.TP"
export_format="jpg"
export_quality="92"
export_height="1200"
export_width=""

# Processing
overwrite=1
border_size="30"
border_color="#000F"

# Metadata
contact_name="Your Name"
contact_email="your@email.com"
contact_url="https://yourwebsite.com"
event="Wedding Smith 2025"
caption="Professional event photography"
tags="wedding smith 2025 celebration"

[_default]
text_font="Arial"
text_color="#FFF8"
text_size="28"
padding="20"

[copyright]
gravity="NorthWest"
text="© 2025 Your Name"
text_effect="shadow"

[website]
gravity="SouthEast"
text="www.yourwebsite.com"
text_size="24"
text_effect="shadow"
alpha="85"

[logo]
gravity="SouthWest"
image="assets/logo.png"
resize="200x200"
padding="30"
alpha="90"
```

## Tips and Best Practices

### Performance

- **Plain text** is faster than shadow/outline effects
- **Fewer overlays** = faster processing
- **Smaller output dimensions** = faster processing
- Use `overwrite=0` to skip already-processed images

### Readability

- Use **semi-transparent colors** for watermarks: `#FFF8`
- Add **text effects** for contrast: `text_effect="shadow"`
- Use **undercolor** on busy backgrounds: `undercolor="#0008"`

### File Organization

- Use **path variables** for organized output:
  ```ini
  export_folder="../_MARKED/$imgyear/$basename"
  ```

### Logo Tips

- Use **PNG with transparency** for best results
- Keep logos **200-300px** for typical photos
- Use **alpha=85-95** for subtle branding

### Font Selection

- **Sans-serif fonts** (Arial, Helvetica) are cleaner
- **Serif fonts** (Times, Georgia) are more traditional
- **Custom fonts** from `/font` directory for branding
