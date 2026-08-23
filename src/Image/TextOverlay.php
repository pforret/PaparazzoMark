<?php

declare(strict_types=1);

namespace Pforret\PaparazzoMark\Image;

class TextOverlay
{
    /** Font file types that can be passed to ImageMagick's -font option */
    private const FONT_EXTENSIONS = ['ttf', 'otf', 'ttc', 'pfa', 'pfb'];

    /** Cache of normalized font name => font file path, per font directory */
    private static array $fontFileCache = [];

    /** Cache of "executable|font name" => whether ImageMagick can render with it */
    private static array $fontProbeCache = [];

    /** Cache of rendered overlays: params hash => composite command */
    private array $overlayCache = [];

    private TempFileManager $tempFileManager;

    private string $magickExecutable;

    public function __construct(TempFileManager $tempFileManager, string $magickExecutable = 'convert')
    {
        $this->tempFileManager = $tempFileManager;
        $this->magickExecutable = $magickExecutable;
    }

    /**
     * Create text overlay and return composite command
     * Ports PrepMagick::overlay_text() logic from lines 39-124
     */
    public function create(array $parameters): string
    {
        // The overlay only depends on the parameters, so render it once
        // and reuse the same temp file for every processed image
        $cacheKey = md5(serialize($parameters));
        if (isset($this->overlayCache[$cacheKey])) {
            return $this->overlayCache[$cacheKey];
        }

        // Extract parameters with defaults
        $text = $parameters['text'] ?? '';
        // Default to a bundled font: 'Courier' and friends are not resolvable
        // on ImageMagick builds without a font registry
        $font = ! empty($parameters['text_font']) ? $parameters['text_font'] : 'Nunito-Regular';
        $size = (int) ($parameters['text_size'] ?? 50);
        $color = $parameters['text_color'] ?? '#FFF';
        $style = strtolower($parameters['text_effect'] ?? '');
        $padding = (int) ($parameters['padding'] ?? 0);
        $gravity = $parameters['gravity'] ?? 'Center';
        $background = $parameters['background'] ?? '#0000';
        $undercolor = $parameters['undercolor'] ?? null;
        $alpha = (int) ($parameters['alpha'] ?? 100);
        $rotation = (int) ($parameters['rotation'] ?? 0);

        // Resolve font path if it's a TTF file
        $font = $this->resolveFontPath($font);

        // Decode HTML entities
        $encoded = html_entity_decode($text, ENT_QUOTES, 'ISO-8859-1');

        // Create unique temp file path
        $begin = preg_replace('#[^a-zA-Z0-9]*#', '', $encoded);
        $begin = substr($begin, 0, 8);
        $tmpTxt = $this->tempFileManager->getTempDir().DIRECTORY_SEPARATOR.
            "_text_{$begin}.{$size}.".substr(md5(serialize($parameters)), 0, 8).'.png';

        $this->tempFileManager->track($tmpTxt);

        // Build ImageMagick command
        $line = "-size 2000x2000 canvas:#0000 -channel RGBA -font \"{$font}\" -pointsize {$size} -gravity {$gravity} ";

        // Calculate angle for italic/rotation
        $angle = 0;
        if (str_contains($style, 'italic')) {
            $angle = 10;
        }
        if ($rotation) {
            $angle += $rotation;
        }

        // Calculate shadow distance
        $shadow = $this->calculateShadowDistance($size);

        // Apply text effects
        if (str_contains($style, 'shadow')) {
            // Shadow effect: offset dark text + sharp color text
            $shadowColor = $this->getContrastColor($color);
            $line .= "-fill \"{$shadowColor}\" ";
            $line .= "-annotate {$rotation}x{$angle}+0+{$shadow} \"{$encoded}\" ";
            $line .= "-annotate {$rotation}x{$angle}+{$shadow}+{$shadow} \"{$encoded}\" ";
            $line .= "-annotate {$rotation}x{$angle}+{$shadow}+0 \"{$encoded}\" ";
            $line .= '-blur 0x1 ';
            $line .= "-fill \"{$color}\" ";
            if ($undercolor) {
                $line .= "-undercolor \"{$undercolor}\" ";
            }
        } elseif (str_contains($style, 'outline')) {
            // Outline effect: 4-direction offset dark text + sharp color text
            $shadowColor = $this->getContrastColor($color);
            $line .= "-fill \"{$shadowColor}\" ";
            $line .= "-annotate {$rotation}x{$angle}+{$shadow}+{$shadow} \"{$encoded}\" ";
            $line .= "-annotate {$rotation}x{$angle}-{$shadow}+{$shadow} \"{$encoded}\" ";
            $line .= "-annotate {$rotation}x{$angle}+{$shadow}-{$shadow} \"{$encoded}\" ";
            $line .= "-annotate {$rotation}x{$angle}-{$shadow}-{$shadow} \"{$encoded}\" ";
            $line .= "-fill \"{$color}\" ";
            if ($undercolor) {
                $line .= "-undercolor \"{$undercolor}\" ";
            }
        } else {
            // Plain text
            $line .= "-fill \"{$color}\" ";
            if ($undercolor) {
                $line .= "-undercolor \"{$undercolor}\" ";
            }
        }

        // Add the main text
        $line .= "-annotate {$rotation}x{$angle}+0+0 \"{$encoded}\" ";

        // Apply alpha transparency
        if ($alpha < 100) {
            $alphaValue = round($alpha / 100, 2);
            $line .= "-alpha on -channel a -evaluate multiply {$alphaValue} ";
        }

        // Trim excess and add padding
        $line .= "-trim +repage -bordercolor \"{$background}\" -border {$padding} -quality 99 ";

        // Execute ImageMagick command
        $this->runMagick($line." \"{$tmpTxt}\"");

        // Return composite command
        return $this->overlayCache[$cacheKey] = "-gravity {$gravity} \"{$tmpTxt}\" -composite";
    }

    /**
     * Calculate shadow distance based on font size
     * Ports logic from PrepMagick lines 72-76
     */
    private function calculateShadowDistance(int $fontSize): int
    {
        if ($fontSize > 60) {
            return (int) round($fontSize / 30);
        }

        return 1;
    }

    /**
     * Get contrast color for shadow/outline
     * Ports CalcShowColor logic from PrepMagick lines 261-268
     */
    private function getContrastColor(string $color): string
    {
        return match ($color) {
            '#FFF', '#FFFF' => '#0008',
            '#000', '#000F' => '#FFF8',
            default => '#0008',
        };
    }

    /**
     * Execute ImageMagick command
     */
    private function runMagick(string $command): array
    {
        $fullCommand = "\"{$this->magickExecutable}\" {$command} 2>&1";
        $output = [];
        exec($fullCommand, $output);

        return $output;
    }

    /**
     * Resolve a configured font to something ImageMagick can render:
     * an existing font file path, a file in the project /font directory,
     * or a font name from ImageMagick's own registry.
     *
     * @throws \RuntimeException when the font cannot be resolved - rendering
     *                           with an unknown font silently produces an empty overlay
     */
    private function resolveFontPath(string $font): string
    {
        if ($font === '') {
            return $font;
        }

        // Explicit path (absolute, or relative to the current directory)
        if (is_file($font)) {
            return realpath($font);
        }

        // File in the project's /font directory, matched on a normalized name so
        // "IM-FELL-DW-Pica-Italic" also finds "IMFellDWPica-Italic.ttf"
        $fontFiles = $this->getFontDirFiles(dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'font');
        $normalized = $this->normalizeFontName($font);
        if (isset($fontFiles[$normalized])) {
            return $fontFiles[$normalized];
        }

        // Font name ImageMagick can resolve itself ("Arial", "Helvetica", ...)
        if ($this->canRenderWith($font)) {
            return $font;
        }

        throw new \RuntimeException(
            "Unknown font: {$font}. Use a font file, a font from the /font directory, ".
            "or an ImageMagick font name - run 'paparazzomark config:fonts' to list them."
        );
    }

    /**
     * Index the font files in a directory by normalized name
     *
     * @return array<string, string> normalized name => absolute path
     */
    private function getFontDirFiles(string $fontDir): array
    {
        if (isset(self::$fontFileCache[$fontDir])) {
            return self::$fontFileCache[$fontDir];
        }

        $fonts = [];
        foreach (glob($fontDir.DIRECTORY_SEPARATOR.'*') ?: [] as $path) {
            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if (! in_array($extension, self::FONT_EXTENSIONS, true)) {
                continue;
            }

            $name = $this->normalizeFontName(pathinfo($path, PATHINFO_FILENAME));
            // First match wins, so a later file never shadows an earlier one
            $fonts[$name] ??= realpath($path);
        }

        return self::$fontFileCache[$fontDir] = $fonts;
    }

    /**
     * Check whether ImageMagick can actually render with this font name.
     * Probing beats reading '-list font': builds that delegate to fontconfig
     * resolve names they do not list, and a name ImageMagick cannot read only
     * produces a warning - the render then silently yields an empty overlay.
     */
    private function canRenderWith(string $font): bool
    {
        $key = $this->magickExecutable.'|'.$font;
        if (isset(self::$fontProbeCache[$key])) {
            return self::$fontProbeCache[$key];
        }

        $output = [];
        $returnVar = 0;
        exec(
            sprintf(
                '"%s" -size 10x10 canvas:#0000 -font "%s" -pointsize 10 -annotate 0x0+0+0 "A" null: 2>&1',
                $this->magickExecutable,
                $font
            ),
            $output,
            $returnVar
        );

        return self::$fontProbeCache[$key] = ($returnVar === 0);
    }

    /**
     * Normalize a font name for comparison: lowercase, without separators
     * or file extension, so "IM-FELL-DW-Pica-Italic", "IMFellDWPica-Italic.ttf"
     * and "im fell dw pica italic" all match
     */
    private function normalizeFontName(string $font): string
    {
        $extension = strtolower(pathinfo($font, PATHINFO_EXTENSION));
        if (in_array($extension, self::FONT_EXTENSIONS, true)) {
            $font = pathinfo($font, PATHINFO_FILENAME);
        }

        return strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $font) ?? $font);
    }
}
