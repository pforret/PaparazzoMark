# Installation Guide

## System Requirements

- **PHP 8.2 or higher**
- **Composer** (for dependency management)
- **ImageMagick** (for image processing)
- **Git** (for cloning the repository)

## Step 1: Install PHP 8.2+

### macOS (Homebrew)

```bash
brew install php@8.2
```

### Ubuntu/Debian

```bash
sudo apt update
sudo apt install php8.2 php8.2-cli php8.2-mbstring php8.2-xml
```

### Windows

Download PHP from [windows.php.net](https://windows.php.net/download/)

## Step 2: Install Composer

### macOS/Linux

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### Windows

Download from [getcomposer.org](https://getcomposer.org/download/)

## Step 3: Install ImageMagick

### macOS (Homebrew)

```bash
brew install imagemagick
```

### Ubuntu/Debian

```bash
sudo apt install imagemagick
```

### Windows

Download from [imagemagick.org](https://imagemagick.org/script/download.php#windows)

### Verify Installation

```bash
convert -version
# Should show: Version: ImageMagick 7.x.x
```

## Step 4: Install PaparazzoMark

### Clone the Repository

```bash
git clone https://github.com/pforret/PaparazzoMark.git
cd PaparazzoMark
```

### Install PHP Dependencies

```bash
composer install
```

This will install:
- `spatie/image` - Image manipulation
- `symfony/console` - CLI framework
- `hassankhan/config` - Configuration management
- `miljar/php-exif` - EXIF metadata handling

### Verify Installation

```bash
php run.php
```

You should see:

```
PaparazzoMark 1.0

Usage:
  command [options] [arguments]

Available commands:
  config
    config:create    Create a default config file
    config:fonts     List all available fonts
  export
    export:photos    Export images with watermark
```

## Step 5: Configure Your Environment

### Create a Config File

```bash
php run.php config:create
```

This creates `pmark.ini` in your current directory.

### Add Custom Fonts (Optional)

Copy your TTF fonts to the `/font` directory:

```bash
cp /path/to/MyFont.ttf /path/to/PaparazzoMark/font/
```

### List Available Fonts

```bash
php run.php config:fonts
```

## Troubleshooting

### ImageMagick Not Found

**Error**: `ImageMagick convert executable not found`

**Solution**:

1. Check if ImageMagick is installed:
   ```bash
   which convert
   ```

2. If not found, install ImageMagick (see Step 3)

3. If installed but not in PATH, specify full path in config:
   ```ini
   prog_im_convert="/usr/local/bin/convert"
   ```

### PHP Version Too Old

**Error**: `This package requires php ^8.2`

**Solution**: Upgrade PHP to 8.2 or higher (see Step 1)

### Composer Not Found

**Error**: `composer: command not found`

**Solution**: Install Composer (see Step 2)

### Font Not Found

**Error**: `Font file not found: MyFont.ttf`

**Solution**:

1. Use font name instead of filename:
   ```ini
   text_font="Arial"
   ```

2. Or copy font to `/font` directory and use:
   ```ini
   text_font="MyFont.ttf"
   ```

3. Or use absolute path:
   ```ini
   text_font="/absolute/path/to/MyFont.ttf"
   ```

## Next Steps

- [Usage Guide](usage.md) - Learn all available commands
- [Configuration Reference](configuration.md) - Customize your watermarks
- [Examples](examples/index.md) - See sample configurations
