<?php

declare(strict_types=1);

namespace Pforret\PaparazzoMark\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'config:create', description: 'Create a default config file')]
class ConfigCreateCommand extends Command
{
    protected function configure(): void
    {
        $this->addOption(
            'output',
            'o',
            InputOption::VALUE_REQUIRED,
            'Output path for config file',
            'pmark.ini'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $outputPath = $input->getOption('output');

        // Check if file already exists
        if (file_exists($outputPath)) {
            if (! $io->confirm("File {$outputPath} already exists. Overwrite?", false)) {
                $io->warning('Cancelled. File not created.');

                return Command::SUCCESS;
            }
        }

        // Generate default config content
        $content = $this->getDefaultConfigContent();

        // Write to file
        file_put_contents($outputPath, $content);

        $io->success("Created config file: {$outputPath}");
        $io->note('Edit this file to customize your watermark settings.');

        return Command::SUCCESS;
    }

    /**
     * Get default configuration content
     * Based on php_version/pmark.example.ini
     */
    private function getDefaultConfigContent(): string
    {
        return <<<'INI'
;: PMARK.INI file
;; Configuration for PaparazzoMark watermarking

[_export]
;; Global export settings
prog_im_convert="convert"
prog_im_identify="identify"
contact_name="Your Name"
contact_email="your.email@example.com"
contact_url="https://yourwebsite.com"

export_folder="../_MARKED/$nowmonth/$basename.MK"
; Available path variables:
;   $basename  = current folder name
;   $nowyear   = current year (e.g., "2025")
;   $nowmonth  = current year-month (e.g., "2025-01")
;   $nowdate   = current date (e.g., "2025-01-15")
;   $imgyear   = image year from EXIF
;   $imgmonth  = image year-month from EXIF
;   $imgdate   = image date from EXIF

export_quality="90"
; Quality: 90-95 for web, 99 for print

export_format="jpg"
; Choose: jpg, png, gif

source_format="jpg,JPG"
; Comma-separated list of source file extensions

export_height="1200"
; Height in pixels (width calculated automatically)

export_width=""
; Leave empty for auto-calculation, or specify width

border_size="40"
; Border width in pixels (optional)

border_color="#000F"
; Border color (optional)

overwrite=1
; 1 = overwrite existing files, 0 = skip existing

[_default]
;; Default values for text overlays
text_font="Arial"
text_color="#FFF8"
text_size="30"
padding="10"

[copyright]
;; Example: Copyright text in corner
gravity="NorthWest"
text="© 2025 Your Name"

[watermark]
;; Example: Watermark text at bottom
gravity="SouthEast"
text="YourWebsite.com"
text_effect="shadow"

[logo]
;; Example: Logo image overlay
gravity="SouthWest"
image="logo.png"
resize="300x300"
padding="20"

INI;
    }
}
