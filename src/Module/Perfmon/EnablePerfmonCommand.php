<?php

namespace Maximaster\BitrixCliCommands\Module\Perfmon;

use Bitrix\Main\Loader;
use CPerfomanceKeeper;
use DateTime;
use DateTimeImmutable;
use Maximaster\BitrixLoader\BitrixLoader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EnablePerfmonCommand extends Command
{
    public const ARG_ACTIVE_TO = 'activeTo';
    public const ARG_ACTIVE_TO_DESCRIPTION = 'Время завершения активности. Формат: такой же как для конструктора DateTime.';
    public const ARG_ACTIVE_TO_DEFAULT = '+5 minutes';

    private BitrixLoader $bitrixLoader;

    public static function getDefaultName(): ?string
    {
        return 'bitrix:perfmon:enable';
    }

    public static function getDefaultDescription(): ?string
    {
        return 'Включить монитор производительности.';
    }

    public function __construct(BitrixLoader $bitrixLoader, ?string $name = null)
    {
        parent::__construct($name);

        $this->bitrixLoader = $bitrixLoader;
    }

    protected function configure(): void
    {
        $this->addArgument(
            self::ARG_ACTIVE_TO,
            InputArgument::OPTIONAL,
            self::ARG_ACTIVE_TO_DESCRIPTION,
            self::ARG_ACTIVE_TO_DEFAULT
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ss = new SymfonyStyle($input, $output);

        $this->bitrixLoader->prologBefore();
        Loader::includeModule('perfmon');

        $activeTo = $this->resolveActiveTo($input->getArgument(self::ARG_ACTIVE_TO));
        if ($activeTo === null) {
            $ss->error(
                sprintf(
                    'Не удалось определить корректное время завершения работы монитора производительности по следующим входным данным: %s.',
                    var_export($input->getArgument(self::ARG_ACTIVE_TO))
                )
            );
            return self::FAILURE;
        }

        CPerfomanceKeeper::SetActive(true, $activeTo->getTimestamp());
        $ss->success(sprintf('Монитор включен до %s.', $activeTo->format('H:i:s')));

        return self::SUCCESS;
    }

    private function resolveActiveTo(mixed $rawActiveTo): ?DateTimeImmutable
    {
        if (is_string($rawActiveTo) === false) {
            return null;
        }

        return new DateTimeImmutable($rawActiveTo);
    }
}
