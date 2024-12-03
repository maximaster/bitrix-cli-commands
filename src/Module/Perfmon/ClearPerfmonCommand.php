<?php

namespace Maximaster\BitrixCliCommands\Module\Perfmon;

use Bitrix\Main\Loader;
use CPerfomanceKeeper;
use CPerfomanceComponent;
use CPerfomanceSQL;
use CPerfomanceHit;
use CPerfomanceError;
use CPerfomanceCache;
use Maximaster\BitrixLoader\BitrixLoader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ClearPerfmonCommand extends Command
{
    private BitrixLoader $bitrixLoader;

    public static function getDefaultName(): ?string
    {
        return 'bitrix:perfmon:clear';
    }

    public static function getDefaultDescription(): ?string
    {
        return 'Очистить данные собранные монитором производительности.';
    }

    public function __construct(BitrixLoader $bitrixLoader, ?string $name = null)
    {
        parent::__construct($name);

        $this->bitrixLoader = $bitrixLoader;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->bitrixLoader->prologBefore();

        Loader::includeModule('perfmon');

        CPerfomanceComponent::Clear();
        CPerfomanceSQL::Clear();
        CPerfomanceHit::Clear();
        CPerfomanceError::Clear();
        CPerfomanceCache::Clear();

        (new SymfonyStyle($input, $output))->success('Очиска завершена.');

        return self::SUCCESS;
    }
}
