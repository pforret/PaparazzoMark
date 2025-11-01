<?php

declare(strict_types=1);

namespace Pforret\PaparazzoMark\Image;

class TempFileManager
{
    private array $tempFiles = [];

    private string $tempDir;

    public function __construct(string $tempDir = '.temp')
    {
        $this->tempDir = $tempDir;

        // Create temp directory if it doesn't exist
        if (! file_exists($this->tempDir)) {
            mkdir($this->tempDir, 0777, true);
            if (! file_exists($this->tempDir)) {
                throw new \RuntimeException("Could not create temp directory: {$this->tempDir}");
            }
        }
    }

    /**
     * Get temp directory path
     */
    public function getTempDir(): string
    {
        return $this->tempDir;
    }

    /**
     * Track a temporary file for later cleanup
     */
    public function track(string $filePath): void
    {
        $this->tempFiles[] = $filePath;
    }

    /**
     * Get list of tracked temp files
     */
    public function getTrackedFiles(): array
    {
        return $this->tempFiles;
    }

    /**
     * Cleanup all tracked temporary files
     */
    public function cleanup(): void
    {
        foreach ($this->tempFiles as $file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }
        $this->tempFiles = [];
    }

    /**
     * Auto-cleanup on destruct
     */
    public function __destruct()
    {
        // Note: Don't auto-cleanup in destructor to allow debugging
        // User must explicitly call cleanup()
    }
}
