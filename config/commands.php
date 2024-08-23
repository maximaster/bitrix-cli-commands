<?php

declare(strict_types=1);

use Maximaster\BitrixLoader\BitrixLoader;

$bitrixLoader = BitrixLoader::fromComposerConfigExtra(__DIR__ . '/../../../../composer.json');
$commandsFactory = require __DIR__ . '/commands_factory.php';

return $commandsFactory($bitrixLoader);
