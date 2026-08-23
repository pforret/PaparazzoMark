<?php

declare(strict_types=1);

namespace Pforret\PaparazzoMark\Image;

class ImageProcessor
{
    private TextOverlay $textOverlay;

    private ImageOverlay $imageOverlay;

    private MetadataWriter $metadataWriter;

    private TempFileManager $tempFileManager;

    private string $magickExecutable;

    public function __construct(
        TempFileManager $tempFileManager,
        string $magickExecutable = 'convert'
    ) {
        $this->tempFileManager = $tempFileManager;
        $this->magickExecutable = $magickExecutable;
        $this->textOverlay = new TextOverlay($tempFileManager, $magickExecutable);
        $this->imageOverlay = new ImageOverlay($tempFileManager, $magickExecutable);
        $this->metadataWriter = new MetadataWriter($tempFileManager);
    }

    /**
     * Process an image with all configured operations
     *
     * @param  string  $inputPath  Path to source image
     * @param  string  $outputPath  Path to output image
     * @param  array  $operations  Array of overlay operations from config sections
     * @param  array  $globalConfig  Global configuration (_export section)
     */
    public function processImage(
        string $inputPath,
        string $outputPath,
        array $operations,
        array $globalConfig
    ): void {
        // Execute ImageMagick
        $this->runMagick($this->buildCommand($inputPath, $outputPath, $operations, $globalConfig));

        if (! file_exists($outputPath)) {
            throw new \RuntimeException("Failed to create output image: {$outputPath}");
        }
    }

    /**
     * Build the full ImageMagick argument string for one image, rendering
     * any overlay temp files that are not cached yet. The result can be
     * passed to runMagick() or executed externally (e.g. in parallel).
     */
    public function buildCommand(
        string $inputPath,
        string $outputPath,
        array $operations,
        array $globalConfig
    ): string {
        // Build ImageMagick command parts
        $commandParts = [];

        // Resize operation
        $height = (int) ($globalConfig['export_height'] ?: 800);
        $width = ! empty($globalConfig['export_width']) ? (int) $globalConfig['export_width'] : 0;
        if ($width > 0) {
            $commandParts[] = "-resize {$width}x{$height}";
        } else {
            $commandParts[] = "-resize x{$height}";
        }

        // Process overlay operations
        foreach ($operations as $operation) {
            // Determine operation type
            if (! empty($operation['text'])) {
                // Text overlay
                $compositeCmd = $this->textOverlay->create($operation);
                $commandParts[] = $compositeCmd;
            } elseif (! empty($operation['image'])) {
                // Image overlay
                $compositeCmd = $this->imageOverlay->create($operation);
                if ($compositeCmd) {
                    $commandParts[] = $compositeCmd;
                }
            }

            // Border (can be combined with text or image)
            if (! empty($operation['border_size'])) {
                $borderColor = $operation['border_color'] ?? '#000F';
                $borderSize = $operation['border_size'];
                $commandParts[] = "-bordercolor \"{$borderColor}\" -border {$borderSize}";
            }
        }

        // Quality
        $quality = (int) ($globalConfig['export_quality'] ?? 95);
        $commandParts[] = "-quality {$quality}";

        // EXIF metadata
        $metadataCmd = $this->metadataWriter->getMetadataCommand($globalConfig);
        if ($metadataCmd) {
            $commandParts[] = $metadataCmd;
        }

        // Build full command
        $command = implode(' ', $commandParts);

        return "\"{$inputPath}\" {$command} \"{$outputPath}\"";
    }

    public function getMagickExecutable(): string
    {
        return $this->magickExecutable;
    }

    /**
     * Execute ImageMagick command
     */
    private function runMagick(string $command): array
    {
        $fullCommand = "\"{$this->magickExecutable}\" {$command} 2>&1";
        $output = [];
        $returnVar = 0;
        exec($fullCommand, $output, $returnVar);

        if ($returnVar !== 0 && ! empty($output)) {
            // Log warning but don't fail (some ImageMagick operations return non-zero even on success)
            // Only throw if output file doesn't exist (checked in caller)
        }

        return $output;
    }
}
