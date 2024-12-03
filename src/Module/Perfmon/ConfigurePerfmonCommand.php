<?php

namespace Maximaster\BitrixCliCommands\Module\Perfmon;

use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Maximaster\BitrixLoader\BitrixLoader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ConfigurePerfmonCommand extends Command
{
    private BitrixLoader $bitrixLoader;

    public const ARG_OPTION_NAME = 'option';
    public const ARG_OPTION_NAME_DESCRIPTION = 'Кодовое имя опции для настройки (%s).';
    public const ARG_OPTION_NAMES = [
        'max_display_url',
        'warning_log',
        'cache_log',
        'large_cache_log',
        'large_cache_size',
        'sql_log',
        'sql_backtrace',
        'slow_sql_log',
        'slow_sql_time',
    ];
    public const ARG_OPTION_VALUE = 'value';
    public const ARG_OPTION_VALUE_DESCRIPTION = 'Значение опции.';

    public const OPT_FORCE = 'force';
    public const OPT_FORCE_SHORT = 'f';
    public const OPT_FORCE_DESCRIPTION = 'Приненить значение опции, даже если такая опция не известна.';

    public static function getDefaultName(): ?string
    {
        return 'bitrix:perfmon:configure';
    }

    public static function getDefaultDescription(): ?string
    {
        return 'Настроить параметры монитора производительности.';
    }

    public function __construct(BitrixLoader $bitrixLoader, ?string $name = null)
    {
        parent::__construct($name);

        $this->bitrixLoader = $bitrixLoader;
    }

    protected function configure(): void
    {
        $this->addArgument(
            self::ARG_OPTION_NAME,
            InputArgument::REQUIRED,
            sprintf(self::ARG_OPTION_NAME_DESCRIPTION, implode(', ', self::ARG_OPTION_NAMES))
        );

        $this->addArgument(self::ARG_OPTION_VALUE, InputArgument::REQUIRED, self::ARG_OPTION_VALUE);

        $this->addOption(self::OPT_FORCE, self::OPT_FORCE_SHORT, InputOption::VALUE_NONE, self::OPT_FORCE_DESCRIPTION);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ss = new SymfonyStyle($input, $output);

        $this->bitrixLoader->prologBefore();
        Loader::includeModule('perfmon');

        $name = $input->getArgument(self::ARG_OPTION_NAME);

        if (in_array($name, self::ARG_OPTION_NAMES) === false) {
            $ss->warning('Опция с таким названием не распознана, известные опции:.');
            $ss->listing(self::ARG_OPTION_NAMES);

            if ($input->getOption(self::OPT_FORCE) === false) {
                $ss->error(
                    sprintf(
                        'Опция не была установлена. Если хотите всё же установить её, добавьте `--%s`.',
                        self::OPT_FORCE
                    )
                );

                return self::FAILURE;
            }
        }

        $value = $input->getArgument(self::ARG_OPTION_VALUE);

        Option::set('perfmon', $name, $value, '');

        $ss->success(sprintf('Настройка `%s` установлена в значение `%s`.', $name, $value));

        return self::SUCCESS;
    }
}
