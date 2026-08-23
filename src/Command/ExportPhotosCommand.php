<?php

declare(strict_types=1);

namespace Pforret\PaparazzoMark\Command;

use Pforret\PaparazzoMark\Config\ConfigLoader;
use Pforret\PaparazzoMark\Config\ConfigValidator;
use Pforret\PaparazzoMark\Config\PathResolver;
use Pforret\PaparazzoMark\Image\ImageProcessor;
use Pforret\PaparazzoMark\Image\TempFileManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'export:photos', description: 'Export images with watermark')]
class ExportPhotosCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Path to config file', 'pmark.ini')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be done without processing')
            ->addOption('debug', 'd', InputOption::VALUE_NONE, 'Keep temp files for debugging')
            ->addOption('jobs', 'j', InputOption::VALUE_REQUIRED, 'Number of images to process in parallel (default: CPU count)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $isDryRun = $input->getOption('dry-run');
        $isDebug = $input->getOption('debug');

        $io->writeln('<info>PaparazzoMark - Photo Watermarking</info>');

        // 1. Find and load config file
        $configPath = $this->findConfigFile($input->getOption('config'), $io);
        if (! $configPath) {
            $io->error('Config file not found. Run "php run.php config:create" to create one.');

            return Command::FAILURE;
        }

        $io->writeln('Config: '.$this->truncatePath($configPath));

        try {
            $config = new ConfigLoader($configPath);
        } catch (\Exception $e) {
            $io->error("Failed to load config: {$e->getMessage()}");

            return Command::FAILURE;
        }

        // 2. Validate config
        $validator = new ConfigValidator;
        if (! $validator->validate($config)) {
            $io->error('Configuration validation failed:');
            foreach ($validator->getErrors() as $error) {
                $io->writeln("  - {$error}");
            }

            return Command::FAILURE;
        }

        // 3. Resolve output directory
        $pathResolver = new PathResolver;
        $exportFolder = $config->getValue('_export', 'export_folder', '../_MARKED');
        $outputDir = $pathResolver->resolve($exportFolder, null, getcwd());

        $io->writeln('Output: '.$this->truncatePath($outputDir));

        // Create output directory if needed
        if (! $isDryRun && ! file_exists($outputDir)) {
            mkdir($outputDir, 0777, true);
            if (! file_exists($outputDir)) {
                $io->error("Could not create output directory: {$outputDir}");

                return Command::FAILURE;
            }
        }

        // 4. Parse source_format and collect images
        $sourceFormats = $config->getValue('_export', 'source_format', 'jpg');
        $formats = array_map('trim', explode(',', $sourceFormats));

        $images = [];
        foreach ($formats as $format) {
            $found = glob('*.'.ltrim($format, '.'));
            if ($found) {
                foreach ($found as $file) {
                    if (is_file($file)) {
                        $images[$file] = pathinfo($file, PATHINFO_FILENAME);
                    }
                }
            }
        }

        if (empty($images)) {
            $io->warning("No images found matching formats: {$sourceFormats}");

            return Command::SUCCESS;
        }

        ksort($images);
        $totalImages = count($images);

        // 5. Read global config
        $globalConfig = $config->getAllValues('_export');

        // 6. Collect overlay operations
        $operations = [];
        $sections = $config->getSections();
        foreach ($sections as $section) {
            $sectionData = $config->getAllValues($section);

            // Determine if this section has any operations
            if (! empty($sectionData['text']) || ! empty($sectionData['image']) || ! empty($sectionData['border_size'])) {
                $operations[] = $sectionData;
            }
        }

        $io->writeln("Images: {$totalImages} | Overlays: ".count($operations));

        if ($isDryRun) {
            $io->note('DRY RUN - No files will be created');
            $io->writeln('Operations configured:');
            foreach ($operations as $i => $op) {
                if (! empty($op['text'])) {
                    $gravity = $op['gravity'] ?? 'Center';
                    $io->writeln("  - Text: {$op['text']} ({$gravity})");
                } elseif (! empty($op['image'])) {
                    $gravity = $op['gravity'] ?? 'Center';
                    $io->writeln("  - Image: {$op['image']} ({$gravity})");
                }
            }

            return Command::SUCCESS;
        }

        // 7. Initialize processing
        $tempFileManager = new TempFileManager;
        $magickExecutable = $config->getValue('_export', 'prog_im_convert', 'convert');
        $processor = new ImageProcessor($tempFileManager, $magickExecutable);

        $exportFormat = $config->getValue('_export', 'export_format', 'jpg');
        $overwrite = (bool) $config->getValue('_export', 'overwrite', false);

        $jobs = (int) ($input->getOption('jobs') ?: $this->detectCpuCount());
        $jobs = max(1, $jobs);

        // 8. Process images
        $io->newLine();
        $progressBar = $io->createProgressBar($totalImages);
        $progressBar->start();

        $processed = 0;
        $skipped = 0;
        $bytesProcessed = 0;
        $startTime = microtime(true);

        // Collect the images that need processing; building the commands up
        // front also renders each overlay temp file exactly once
        $queue = [];
        foreach ($images as $imagePath => $imageName) {
            $outputPath = $outputDir.DIRECTORY_SEPARATOR."{$imageName}.{$exportFormat}";

            $needsProcessing = ! file_exists($outputPath) ||
                $overwrite ||
                filemtime($imagePath) > filemtime($outputPath);

            if (! $needsProcessing) {
                $skipped++;
                $progressBar->advance();

                continue;
            }

            try {
                $queue[] = [
                    'input' => $imagePath,
                    'output' => $outputPath,
                    'command' => $processor->buildCommand($imagePath, $outputPath, $operations, $globalConfig),
                ];
            } catch (\Exception $e) {
                $io->newLine(2);
                $io->warning("Failed to process {$imagePath}: {$e->getMessage()}");
                if ($output->isVerbose()) {
                    $io->writeln($e->getTraceAsString());
                }
                $progressBar->advance();
            }
        }

        // Run up to $jobs ImageMagick processes concurrently
        $magick = $processor->getMagickExecutable();
        $running = [];
        $failures = [];

        while ($queue || $running) {
            while ($queue && count($running) < $jobs) {
                $job = array_shift($queue);
                $process = proc_open(
                    "\"{$magick}\" {$job['command']} 2>&1",
                    [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                    $pipes
                );
                if ($process === false) {
                    $failures[$job['input']] = 'could not start ImageMagick process';
                    $progressBar->advance();

                    continue;
                }
                stream_set_blocking($pipes[1], false);
                $running[] = ['process' => $process, 'pipes' => $pipes, 'job' => $job];
            }

            usleep(10000);

            foreach ($running as $key => $worker) {
                if (proc_get_status($worker['process'])['running']) {
                    continue;
                }

                foreach ($worker['pipes'] as $pipe) {
                    fclose($pipe);
                }
                proc_close($worker['process']);
                unset($running[$key]);

                $job = $worker['job'];
                if (file_exists($job['output'])) {
                    $processed++;
                    $bytesProcessed += filesize($job['input']);
                } else {
                    $failures[$job['input']] = "failed to create output image: {$job['output']}";
                }
                $progressBar->advance();
            }
        }

        foreach ($failures as $imagePath => $reason) {
            $io->newLine(2);
            $io->warning("Failed to process {$imagePath}: {$reason}");
        }

        $progressBar->finish();
        $io->newLine();

        // 9. Display summary
        $elapsed = microtime(true) - $startTime;
        $picsPerSec = $processed > 0 ? round($processed / $elapsed, 1) : 0;
        $mbProcessed = round($bytesProcessed / 1000000, 1);
        $mbPerSec = $elapsed > 0 ? round($mbProcessed / $elapsed, 1) : 0;

        $io->writeln(sprintf(
            '<info>Done!</info> Processed: %d | Skipped: %d | Time: %.1fs | Speed: %.1f pics/s, %.1f MB/s',
            $processed,
            $skipped,
            $elapsed,
            $picsPerSec,
            $mbPerSec
        ));

        // 10. Cleanup temp files
        if (! $isDebug) {
            $tempFileManager->cleanup();
        } else {
            $io->writeln('Debug: Temp files in '.$tempFileManager->getTempDir());
        }

        // 11. Open output folder
        $this->openOutputFolder($outputDir);

        return Command::SUCCESS;
    }

    /**
     * Detect the number of CPU cores for the default parallelism
     */
    private function detectCpuCount(): int
    {
        $count = match (PHP_OS_FAMILY) {
            'Windows' => (int) getenv('NUMBER_OF_PROCESSORS'),
            'Darwin' => (int) shell_exec('sysctl -n hw.ncpu'),
            default => (int) shell_exec('nproc 2>/dev/null'),
        };

        return $count > 0 ? $count : 4;
    }

    /**
     * Find config file in standard locations
     */
    private function findConfigFile(string $configOption, SymfonyStyle $io): ?string
    {
        // If config option is absolute path or exists, use it
        if (file_exists($configOption)) {
            return realpath($configOption);
        }

        // Search in standard locations
        $searchPaths = [
            getcwd(),
            dirname(getcwd()),
            __DIR__.'/../..',
        ];

        return ConfigLoader::findConfigFile($searchPaths, $configOption);
    }

    /**
     * Open output folder in file manager
     * Ports logic from pmark.php lines 165-177
     */
    private function openOutputFolder(string $folder): void
    {
        if (! is_dir($folder)) {
            return;
        }

        $folder = realpath($folder);

        switch (PHP_OS_FAMILY) {
            case 'Windows':
                exec("explorer \"{$folder}\"");
                break;
            case 'Darwin':
                exec("open \"{$folder}\"");
                break;
            case 'Linux':
                exec("xdg-open \"{$folder}\"");
                break;
        }
    }

    /**
     * Truncate long paths to keep output compact
     */
    private function truncatePath(string $path, int $maxLength = 80): string
    {
        if (strlen($path) <= $maxLength) {
            return $path;
        }

        return '...'.substr($path, -$maxLength);
    }
}
