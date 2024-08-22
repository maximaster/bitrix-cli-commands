<?php
/**
 * Внимание! Подключать данный файл можно только при условии, если проектный
 * composer.json файл находится в той же директории, что и vendor-директория
 * с данным пакетом.
 */
declare(strict_types=1);

use Maximaster\BitrixCliCommands\Module\Main\ClearCacheCommand;
use Maximaster\BitrixLoader\BitrixLoader;

$bitrixLoader = BitrixLoader::fromComposerConfigExtra(__DIR__ . '/../../../composer.json');

return [
    new ClearCacheCommand($bitrixLoader),
];
