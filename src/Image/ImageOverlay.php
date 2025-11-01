<?php

declare(strict_types=1);

namespace Pforret\PaparazzoMark\Image;

class ImageOverlay
{
    private TempFileManager $tempFileManager;

    private string $magickExecutable;

    public function __construct(TempFileManager $tempFileManager, string $magickExecutable = 'convert')
    {
        $this->tempFileManager = $tempFileManager;
        $this->magickExecutable = $magickExecutable;
    }

    /**
     * Create image overlay and return composite command
     * Ports PrepMagick::overlay_image() logic from lines 126-166
     */
    public function create(array $parameters): string|false
    {
        // Get image path
        $imagePath = $parameters['image'] ?? null;
        if (! $imagePath) {
            return false;
        }

        // Check if file exists
        if (! file_exists($imagePath)) {
            // Try relative to project root
            $projectRoot = dirname(__DIR__, 2);
            $fullPath = $projectRoot.DIRECTORY_SEPARATOR.$imagePath;
            if (file_exists($fullPath)) {
                $imagePath = $fullPath;
            } else {
                throw new \RuntimeException("Image file not found: {$imagePath}");
            }
        }

        $imagePath = realpath($imagePath);
        $fileName = basename($imagePath);

        // Extract parameters with defaults
        $padding = (int) ($parameters['padding'] ?? 0);
        $background = $parameters['background'] ?? '#0000';
        $resize = $parameters['resize'] ?? null;
        $gravity = $parameters['gravity'] ?? 'Center';
        $alpha = (int) ($parameters['alpha'] ?? 100);

        // Create unique temp file path
        $begin = preg_replace('#[^a-zA-Z0-9]*#', '', basename($imagePath));
        $begin = substr($begin, 0, 8);
        $tmpImg = $this->tempFileManager->getTempDir().DIRECTORY_SEPARATOR.
            "_img_{$begin}.".substr(md5(serialize($parameters)), 0, 8).'.png';

        $this->tempFileManager->track($tmpImg);

        // Build ImageMagick command
        $line = '';

        if ($resize) {
            $line .= "-resize {$resize} ";
        }

        if ($alpha < 100) {
            $alphaValue = round($alpha / 100, 2);
            $line .= "-alpha on -channel a -evaluate multiply {$alphaValue} ";
        }

        if ($padding) {
            $line .= "-bordercolor \"{$background}\" -border {$padding} ";
        }

        $line .= '-quality 99 ';

        // Execute ImageMagick command
        $this->runMagick("\"{$imagePath}\" {$line} \"{$tmpImg}\"");

        if (! file_exists($tmpImg)) {
            throw new \RuntimeException("Could not create image overlay: {$imagePath}");
        }

        // Return composite command
        return "-gravity {$gravity} \"{$tmpImg}\" -composite";
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
}
