<?php

namespace Maximaster\BitrixCliCommands\Module\Perfmon;

use Bitrix\Main\Loader;
use CPerfomanceKeeper;
use DatePeriod;
use Maximaster\BitrixLoader\BitrixLoader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DisablePerfmonCommand extends Command
{
    private BitrixLoader $bitrixLoader;

    public static function getDefaultName(): ?string
    {
        return 'bitrix:perfmon:disable';
    }

    public static function getDefaultDescription(): ?string
    {
        return 'Отключить монитор производительности.';
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

        CPerfomanceKeeper::SetActive(false);
        (new SymfonyStyle($input, $output))->success('Монитор производительности успешно выключен.');

        return self::SUCCESS;
    }
}
