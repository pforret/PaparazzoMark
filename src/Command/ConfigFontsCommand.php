<?php

namespace Pforret\PaparazzoMark\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'config:fonts', description: 'List all available fonts')]
class ConfigFontsCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $defaultFonts = [];
        // get font list from imagemagick program
        exec('convert -list font | grep Font: | grep -v " \." | grep "[aeiouy]" | sed "s/ Font: //"', $defaultFonts);

        $includedFonts = [];
        // get font list from /font/ folder with TTF files
        $fontFolder = __DIR__.'/../../font/';
        $files = scandir($fontFolder);
        foreach ($files as $file) {
            if (preg_match('/\.ttf$/', $file)) {
                $includedFonts[] = $file;
            }
        }
        $fonts = array_merge($defaultFonts, $includedFonts);
        sort($fonts);

        foreach ($fonts as $font) {
            $output->writeln($font);
        }

        return Command::SUCCESS;
    }
}
