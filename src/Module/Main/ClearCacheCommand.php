<?php

declare(strict_types=1);

namespace Maximaster\BitrixCliCommands\Module\Main;

use Bitrix\Landing\Block;
use Bitrix\Main\Application;
use Bitrix\Main\Composite\Page;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use CBitrixComponent;
use InvalidArgumentException;
use Maximaster\BitrixEnums\Main\CacheType;
use Maximaster\BitrixEnums\Main\Module;
use Maximaster\BitrixLoader\BitrixLoader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Консольная команда для удаления кеша Битрикс.
 */
class ClearCacheCommand extends Command
{
    public const OPT_TYPE = 'type';
    public const OPT_TYPE_SHORT = 't';
    public const OPT_TYPE_DESCRIPTION = 'Типы удаляемого кеша: %s. Удаляется все, если тип не указан.';

    private BitrixLoader $bitrixLoader;

    public static function getDefaultName(): ?string
    {
        return 'bitrix:cache:clear';
    }

    public static function getDefaultDescription(): ?string
    {
        return 'Удалить кеш Битрикс.';
    }

    public function __construct(BitrixLoader $bitrixLoader, ?string $name = null)
    {
        parent::__construct($name);

        $this->bitrixLoader = $bitrixLoader;
    }

    protected function configure(): void
    {
        $this->addOption(
            self::OPT_TYPE,
            self::OPT_TYPE_SHORT,
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            sprintf(self::OPT_TYPE_DESCRIPTION, implode(', ', array_values(CacheType::toArray()))),
        );
    }

    /**
     * @throws LoaderException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->bitrixLoader->prologBefore();

        $ss = new SymfonyStyle($input, $output);

        $clearedTypes = $this->resolveTypes($input);

        foreach ($clearedTypes as $clearedType) {
            match ($clearedType) {
                CacheType::LANDING => $this->clearLandingCache(),
                CacheType::MENU => $this->clearMenuCache(),
                CacheType::MANAGED => $this->clearManagedCache(),
                CacheType::HTML => $this->clearHtmlCache(),
                default => throw new InvalidArgumentException(
                    sprintf('Не поддерживаемый тип кеша для удаления: %s.', $clearedType)
                ),
            };
        }

        $ss->success(sprintf('Удаление кеша завершено (%s).', implode(', ', $clearedTypes)));

        return self::SUCCESS;
    }

    /**
     * @psalm-return non-empty-list<non-empty-string>
     */
    private function resolveTypes(InputInterface $input): array
    {
        $types = $input->getOption(self::OPT_TYPE);
        if (is_array($types) === false) {
            throw new InvalidArgumentException(
                sprintf(
                    'Ожидалось, что опции %s будет содержать массив, получено %s.',
                    self::OPT_TYPE,
                    get_debug_type($types)
                )
            );
        }

        foreach ($types as $type) {
            if (is_string($type) === false || $type === '') {
                throw new InvalidArgumentException(
                    sprintf(
                        'Все значения опции %s должны быть типа string, получено %s.',
                        self::OPT_TYPE,
                        get_debug_type($type)
                    )
                );
            }
        }

        return count($types) === 0 ? array_values(CacheType::toArray()) : $types;
    }

    /**
     * @throws LoaderException
     */
    private function clearLandingCache(): void
    {
        if (Loader::includeModule(Module::LANDING)) {
            Block::clearRepositoryCache();
        }
    }

    private function clearMenuCache(): void
    {
        $managedCache = Application::getInstance()->getManagedCache();

        $managedCache->cleanDir('menu');
        CBitrixComponent::clearComponentCache('bitrix:menu');
    }

    private function clearManagedCache(): void
    {
        $cache = Cache::createInstance();
        $managedCache = Application::getInstance()->getManagedCache();

        $cache->cleanDir(false, 'stack_cache');
        $managedCache->cleanAll();
    }

    private function clearHtmlCache(): void
    {
        Page::getInstance()->deleteAll();
    }
}
