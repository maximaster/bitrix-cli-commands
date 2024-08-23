<?php

declare(strict_types=1);

use Maximaster\BitrixCliCommands\Module\Main\ClearCacheCommand;
use Maximaster\BitrixLoader\BitrixLoader;

return static fn (BitrixLoader $bitrixLoader) => [
    new ClearCacheCommand($bitrixLoader),
];
