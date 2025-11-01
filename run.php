#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

use Pforret\PaparazzoMark\Command\ConfigCreateCommand;
use Pforret\PaparazzoMark\Command\ConfigFontsCommand;
use Pforret\PaparazzoMark\Command\ExportPhotosCommand;
use Symfony\Component\Console\Application;

$application = new Application;
$application->add(new ConfigFontsCommand);
$application->add(new ConfigCreateCommand);
$application->add(new ExportPhotosCommand);

try {
    $application->run();
} catch (Exception $e) {
}
