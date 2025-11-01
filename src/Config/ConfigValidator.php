<?php

declare(strict_types=1);

namespace Pforret\PaparazzoMark\Config;

class ConfigValidator
{
    private array $errors = [];

    /**
     * Validate configuration
     */
    public function validate(ConfigLoader $config): bool
    {
        $this->errors = [];

        // Check required fields
        $this->validateRequiredFields($config);

        // Check ImageMagick executables
        $this->validateImageMagick($config);

        // Check file paths in overlay sections
        $this->validateFilePaths($config);

        return empty($this->errors);
    }

    /**
     * Get validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Validate required fields in _export section
     */
    private function validateRequiredFields(ConfigLoader $config): void
    {
        $required = ['source_format', 'export_folder'];

        foreach ($required as $field) {
            $value = $config->getValue('_export', $field);
            if (empty($value)) {
                $this->errors[] = "Missing required field '_export.$field' in configuration";
            }
        }
    }

    /**
     * Validate ImageMagick executables are accessible
     */
    private function validateImageMagick(ConfigLoader $config): void
    {
        $convertExe = $config->getValue('_export', 'prog_im_convert', 'convert');
        $identifyExe = $config->getValue('_export', 'prog_im_identify', 'identify');

        // Try to execute and capture version
        $convertCheck = $this->checkExecutable($convertExe);
        $identifyCheck = $this->checkExecutable($identifyExe);

        if (! $convertCheck) {
            $this->errors[] = "ImageMagick convert executable not found or not working: {$convertExe}. ".
                "Please install ImageMagick or set 'prog_im_convert' in config.";
        }

        if (! $identifyCheck) {
            $this->errors[] = "ImageMagick identify executable not found or not working: {$identifyExe}. ".
                "Please install ImageMagick or set 'prog_im_identify' in config.";
        }
    }

    /**
     * Check if executable is available
     */
    private function checkExecutable(string $executable): bool
    {
        // Try to run with --version
        $output = [];
        $returnVar = 0;
        @exec("{$executable} --version 2>&1", $output, $returnVar);

        return $returnVar === 0 || $returnVar === 1; // Some versions return 1 for --version
    }

    /**
     * Validate file paths in overlay sections (fonts, logos)
     */
    private function validateFilePaths(ConfigLoader $config): void
    {
        $sections = $config->getSections();

        foreach ($sections as $section) {
            $values = $config->getAllValues($section);

            // Check image paths
            if (isset($values['image']) && ! empty($values['image'])) {
                $imagePath = $values['image'];
                if (! file_exists($imagePath)) {
                    // Try relative to project root
                    $projectRoot = dirname(__DIR__, 2);
                    $fullPath = $projectRoot.DIRECTORY_SEPARATOR.$imagePath;
                    if (! file_exists($fullPath)) {
                        $this->errors[] = "Image file not found in section '{$section}': {$imagePath}";
                    }
                }
            }

            // Check font paths (if absolute path is provided)
            if (isset($values['text_font']) && ! empty($values['text_font'])) {
                $fontPath = $values['text_font'];
                // Only validate if it looks like a file path (contains / or \)
                if ((str_contains($fontPath, '/') || str_contains($fontPath, '\\')) && ! file_exists($fontPath)) {
                    $this->errors[] = "Font file not found in section '{$section}': {$fontPath}. ".
                        'Use font name from ImageMagick registry or run php run.php config:fonts to list available fonts.';
                }
            }
        }
    }
}
