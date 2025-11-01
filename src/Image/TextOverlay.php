<?php

declare(strict_types=1);

namespace Pforret\PaparazzoMark\Image;

class TextOverlay
{
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
        // Extract parameters with defaults
        $text = $parameters['text'] ?? '';
        $font = $parameters['text_font'] ?? 'Courier';
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
        return "-gravity {$gravity} \"{$tmpTxt}\" -composite";
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
     * Resolve font path - if it's a TTF file, look in /font directory
     */
    private function resolveFontPath(string $font): string
    {
        // If it's already an absolute path and exists, use it
        if (file_exists($font)) {
            return realpath($font);
        }

        // If it looks like a font file (ends with .ttf, .otf, etc.)
        if (preg_match('/\.(ttf|otf|TTF|OTF)$/', $font)) {
            // Try to find it in the project's /font directory
            $projectRoot = dirname(__DIR__, 2);
            $fontPath = $projectRoot.DIRECTORY_SEPARATOR.'font'.DIRECTORY_SEPARATOR.$font;

            if (file_exists($fontPath)) {
                return realpath($fontPath);
            }

            // Try without the extension (maybe it's already in the path)
            $baseName = pathinfo($font, PATHINFO_FILENAME);
            $possiblePath = $projectRoot.DIRECTORY_SEPARATOR.'font'.DIRECTORY_SEPARATOR.$baseName;
            foreach (['.ttf', '.TTF', '.otf', '.OTF'] as $ext) {
                if (file_exists($possiblePath.$ext)) {
                    return realpath($possiblePath.$ext);
                }
            }
        }

        // Otherwise, return as-is (might be a font name in ImageMagick's registry)
        return $font;
    }
}
