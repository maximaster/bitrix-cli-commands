<?php

declare(strict_types=1);

use Maximaster\BitrixCliCommands\Module\Main\ClearCacheCommand;
use Maximaster\BitrixCliCommands\Module\Perfmon\ClearPerfmonCommand;
use Maximaster\BitrixCliCommands\Module\Perfmon\ConfigurePerfmonCommand;
use Maximaster\BitrixCliCommands\Module\Perfmon\DisablePerfmonCommand;
use Maximaster\BitrixCliCommands\Module\Perfmon\EnablePerfmonCommand;
use Maximaster\BitrixLoader\BitrixLoader;

return static fn(BitrixLoader $bitrixLoader) => [
    new ClearCacheCommand($bitrixLoader),
    new ClearPerfmonCommand($bitrixLoader),
    new ConfigurePerfmonCommand($bitrixLoader),
    new DisablePerfmonCommand($bitrixLoader),
    new EnablePerfmonCommand($bitrixLoader),
];
