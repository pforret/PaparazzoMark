# Configuration Examples

Real-world configuration examples for different use cases.

## Minimal Watermark

Simple website watermark in corner.

```ini
[_export]
prog_im_convert="convert"
source_format="jpg,JPG"
export_folder="../_MARKED"
export_height="1200"
export_quality="90"
export_format="jpg"
overwrite=1

[watermark]
gravity="SouthEast"
text="www.yourwebsite.com"
text_color="#FFF8"
text_size="24"
padding="20"
```

**Use case**: Quick social media sharing with subtle branding.

---

## Professional Event Photography

Copyright notice and photographer credit.

```ini
[_export]
prog_im_convert="convert"
prog_im_identify="identify"
source_format="jpg,JPG"
export_folder="../_MARKED/$nowmonth/$basename.TP"
export_height="1200"
export_quality="92"
export_format="jpg"
overwrite=0

# Metadata
contact_name="Tango Paparazzo"
contact_email="tangopaparazzo@gmail.com"
contact_url="https://tangopaparazzo.com"
event="Amarcord Marathon Bologna 2025"
caption="Professional tango event photography"
tags="amarcord marathon bologna italia 2025 tango"

[_default]
text_font="Nunito-Bold.ttf"
text_color="#FFF"
text_size="28"
padding="30"

[copyright]
gravity="South"
text="© 2025 tangopaparazzo.com"
text_effect="shadow"
alpha="90"
```

**Use case**: Professional event photography with EXIF metadata embedded.

**Features**:
- Organized output folder by date and event name
- EXIF metadata for photo management
- Shadow effect for readability
- Custom font for branding

---

## Wedding Photography

Multiple text overlays with photographer and couple names.

```ini
[_export]
prog_im_convert="convert"
source_format="jpg,JPG"
export_folder="../Wedding-$basename-Watermarked"
export_height="1500"
export_quality="95"
export_format="jpg"
border_size="40"
border_color="#000F"
overwrite=1

contact_name="Wedding Photography Co."
contact_email="info@weddingphoto.com"
event="Smith-Johnson Wedding 2025"

[_default]
text_font="Arial"
text_color="#FFF8"
padding="25"

[photographer]
gravity="NorthWest"
text="© Wedding Photography Co."
text_size="22"
text_effect="shadow"

[couple]
gravity="North"
text="Sarah & Michael - June 15, 2025"
text_size="32"
text_font="Georgia"
text_effect="shadow"

[website]
gravity="SouthEast"
text="www.weddingphoto.com"
text_size="20"
alpha="80"
```

**Use case**: Wedding albums with elegant styling.

**Features**:
- Border for classic look
- Couple names prominently displayed
- Photographer credit
- High quality for prints (95%)

---

## Logo Watermark with Text

Professional branding with logo and text.

```ini
[_export]
prog_im_convert="convert"
source_format="jpg,JPG,png,PNG"
export_folder="../_BRANDED"
export_height="1200"
export_quality="90"
export_format="jpg"
overwrite=1

contact_name="Studio Photography"
contact_url="https://studioexample.com"

[logo]
gravity="SouthWest"
image="assets/studio-logo.png"
resize="250x250"
padding="30"
alpha="90"

[copyright]
gravity="SouthEast"
text="© 2025 Studio Photography"
text_size="24"
text_color="#FFF"
text_effect="shadow"
padding="30"
```

**Use case**: Studio portfolio with professional branding.

**Features**:
- PNG logo with transparency
- Text and logo on opposite corners
- Clean, professional look

---

## Diagonal Watermark

Large diagonal watermark to prevent unauthorized use.

```ini
[_export]
prog_im_convert="convert"
source_format="jpg"
export_folder="../_PROTECTED"
export_height="800"
export_quality="85"
export_format="jpg"
overwrite=1

[watermark]
gravity="Center"
text="SAMPLE - NOT FOR DISTRIBUTION"
text_font="Arial-Bold"
text_size="72"
text_color="#FFFFFF30"
text_effect="outline"
rotation="45"
```

**Use case**: Protect sample images from unauthorized use.

**Features**:
- Large semi-transparent text
- Diagonal rotation
- Center placement
- Outline effect for visibility

---

## Sponsor Branding

Event photos with sponsor logos.

```ini
[_export]
prog_im_convert="convert"
source_format="jpg,JPG"
export_folder="../_EVENT/$basename"
export_height="1200"
export_quality="92"
export_format="jpg"
overwrite=1

event="Corporate Event 2025"
contact_name="Event Photography Pro"

[sponsor_main]
gravity="NorthEast"
image="/path/to/main-sponsor-logo.png"
resize="300x300"
padding="20"
alpha="95"

[sponsor_secondary]
gravity="NorthWest"
image="/path/to/secondary-sponsor.png"
resize="200x200"
padding="20"
alpha="90"

[photographer]
gravity="SouthEast"
text="Photos: EventPhotoPro.com"
text_size="22"
text_color="#FFF8"
text_effect="shadow"
padding="25"
```

**Use case**: Corporate events with sponsor visibility requirements.

**Features**:
- Multiple sponsor logos
- Different sizes for sponsor hierarchy
- Photographer credit maintained

---

## Social Media Optimized

Square format for Instagram with prominent branding.

```ini
[_export]
prog_im_convert="convert"
source_format="jpg,JPG"
export_folder="../_INSTAGRAM"
export_height="1080"
export_width="1080"
export_quality="85"
export_format="jpg"
overwrite=1

[brand]
gravity="South"
text="@YourUsername · YourWebsite.com"
text_size="32"
text_color="#FFF"
text_effect="shadow"
undercolor="#0008"
padding="40"
```

**Use case**: Social media posting with strong branding.

**Features**:
- Square aspect ratio (1:1)
- Social media handle
- Background behind text for readability
- Optimized file size

---

## Print-Quality Portfolio

Maximum quality for portfolio prints.

```ini
[_export]
prog_im_convert="convert"
source_format="jpg,JPG"
export_folder="../_PORTFOLIO"
export_height="3000"
export_width="4500"
export_quality="99"
export_format="jpg"
overwrite=1

contact_name="Professional Photographer"
contact_url="https://portfolio-site.com"

[signature]
gravity="SouthEast"
text="Your Name Photography"
text_font="Georgia-Italic"
text_size="48"
text_color="#FFF8"
text_effect="shadow"
padding="60"
alpha="85"
```

**Use case**: High-resolution portfolio prints.

**Features**:
- Large dimensions (4500x3000)
- Maximum quality (99)
- Elegant signature-style watermark
- Subtle transparency

---

## Multi-Event Template

Reusable template for event series.

```ini
[_export]
prog_im_convert="convert"
prog_im_identify="identify"
source_format="jpg,JPG"
export_folder="../_MARKED/$imgyear/$imgmonth/$basename"
export_height="1200"
export_quality="92"
export_format="jpg"
overwrite=0

# Update these for each event
contact_name="Event Photographer"
contact_email="events@example.com"
contact_url="https://eventphoto.example.com"
event="UPDATE EVENT NAME HERE"
tags="UPDATE TAGS HERE"

[_default]
text_font="Nunito-Bold.ttf"
text_color="#FFF"
text_size="26"
padding="25"

[copyright]
gravity="SouthWest"
text="© 2025 EventPhoto.com"
text_effect="shadow"
alpha="88"

[event_name]
gravity="North"
text="Event: [UPDATE THIS]"
text_size="32"
text_effect="shadow"
undercolor="#0008"
```

**Use case**: Template for multiple events with consistent branding.

**Features**:
- Automatic folder organization by date
- Skip re-processing with `overwrite=0`
- Comments for easy customization
- EXIF date-based organization

---

## Tips for Creating Your Own

### Start Simple
Begin with one text overlay, then add more as needed.

### Test with Dry Run
Always test with `--dry-run` before processing:
```bash
php run.php export:photos --dry-run
```

### Adjust for Your Images
- **Bright images**: Use darker text (`#000`)
- **Dark images**: Use lighter text (`#FFF`)
- **Busy backgrounds**: Use `text_effect="shadow"` or `undercolor`

### Font Selection
List available fonts:
```bash
php run.php config:fonts
```

### Optimize for Purpose
- **Web**: quality=90, height=1200
- **Print**: quality=95-99, height=2000+
- **Social**: specific dimensions (1080x1080 for Instagram)

### Performance
- Use `overwrite=0` to skip already-processed images
- Reduce overlay count for faster processing
- Plain text is faster than effects

---

## More Examples

Looking for more specific examples? Check the [Configuration Reference](../configuration.md) for detailed explanations of all options.
