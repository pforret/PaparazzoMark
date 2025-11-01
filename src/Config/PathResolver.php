<?php

declare(strict_types=1);

namespace Pforret\PaparazzoMark\Config;

use PHPExif\Reader\Reader;

class PathResolver
{
    /**
     * Resolve path variables like $basename, $nowdate, $imgyear etc.
     * Ports legacy resolve_dir() function from pmark.php
     */
    public function resolve(string $path, ?string $sampleImagePath = null, ?string $workingDirectory = null): string
    {
        $workingDirectory = $workingDirectory ?? getcwd();

        // Build replacement map
        $replacements = [
            '$basename' => basename($workingDirectory),
            '$nowyear' => date('Y'),
            '$nowmonth' => date('Y-m'),
            '$nowdate' => date('Y-m-d'),
        ];

        // If path contains $img* variables, we need EXIF data
        if (str_contains($path, '$img')) {
            $imageDate = $this->getImageDate($sampleImagePath, $workingDirectory);

            $replacements['$imgyear'] = date('Y', $imageDate);
            $replacements['$imgmonth'] = date('Y-m', $imageDate);
            $replacements['$imgdate'] = date('Y-m-d', $imageDate);
        }

        // Replace all variables
        return str_replace(array_keys($replacements), array_values($replacements), $path);
    }

    /**
     * Get image date from EXIF or file modification time
     */
    private function getImageDate(?string $imagePath, string $workingDirectory): int
    {
        // If no image provided, find first image in directory
        if (! $imagePath) {
            $images = glob($workingDirectory.DIRECTORY_SEPARATOR.'*.{jpg,JPG,jpeg,JPEG,png,PNG}', GLOB_BRACE);
            if (empty($images)) {
                return time(); // Fallback to current time
            }
            $imagePath = $images[0];
        }

        if (! file_exists($imagePath)) {
            return time(); // Fallback to current time
        }

        try {
            // Try to read EXIF date
            $reader = Reader::factory(Reader::TYPE_NATIVE);
            $exif = $reader->read($imagePath);

            if ($exif && $exif->getCreationDate()) {
                return $exif->getCreationDate()->getTimestamp();
            }
        } catch (\Exception $e) {
            // EXIF reading failed, continue to fallback
        }

        // Fallback to file modification time
        return filemtime($imagePath) ?: time();
    }
}
