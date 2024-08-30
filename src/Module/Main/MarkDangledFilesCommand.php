<?php

declare(strict_types=1);

namespace Maximaster\BitrixCliCommands\Module\Main;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Maximaster\BitrixTableClasses\Table\Main\FileTable;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Команда отметки файлов ФС на которых не ссылается b_files.
 */
class MarkDangledFilesCommand extends Command
{
    public const OPT_DRY_RUN = 'dry-run';
    public const DEFAULT_DANGLED_POSTFIX = '.dangled';

    protected static $defaultName = 'bitrix:main:mark-dangled-files';

    private string $uploadRoot;

    /** @var string[] */
    private array $usedModules;
    private string $dangledPostfix;

    /**
     * @param string[] $usedModules
     */
    public function __construct(
        string $uploadRoot,
        array $usedModules,
        string $dangledPostfix = self::DEFAULT_DANGLED_POSTFIX
    ) {
        parent::__construct();

        $this->uploadRoot = $uploadRoot;
        $this->usedModules = $usedModules;
        $this->dangledPostfix = $dangledPostfix;
    }

    protected function configure(): void
    {
        parent::configure();

        $this->addOption(self::OPT_DRY_RUN, null, InputOption::VALUE_NONE, 'Не вносить изменения, лишь вывести отчёт');
    }

    /**
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ss = new SymfonyStyle($input, $output);

        $dryRun = (bool) $input->getOption(self::OPT_DRY_RUN);

        $moduleFolders = [];
        foreach ($this->usedModules as $usedModule) {
            $moduleFolders[] = $this->uploadRoot . DIRECTORY_SEPARATOR . $usedModule;
        }

        /** @var SplFileInfo[] $uploadedFiles */
        $uploadedFiles = Finder::create()
            ->in($moduleFolders)
            ->files()
            ->notName("*$this->dangledPostfix")
            ->getIterator();

        $dangedFiles = [];
        foreach ($uploadedFiles as $uploadedFile) {
            try {
                $hasStoredFile = FileTable::hasRow([
                    '=' . FileTable::FILE_NAME => $uploadedFile->getBasename(),
                    '=' . FileTable::SUBDIR => $this->extractSubdir($uploadedFile),
                ]);
            } catch (RuntimeException $exception) {
                $ss->error(sprintf('Ошибка нахождения файла: %s', $exception->getMessage()));

                return self::FAILURE;
            }

            if ($hasStoredFile) {
                continue;
            }

            $dangedFiles[] = $uploadedFile->getPathname();
        }

        $dangledCount = count($dangedFiles);
        if ($dangledCount === 0) {
            $ss->success('На все файлы есть ссылки в СУБД.');

            return self::SUCCESS;
        }

        $ss->error(sprintf('Следующие файлы не сохранены в базе (%d):', $dangledCount));
        $ss->listing($dangedFiles);

        if ($dryRun) {
            return self::FAILURE;
        }

        foreach ($dangedFiles as $dangedFile) {
            if (rename($dangedFile, implode('', [$dangedFile, $this->dangledPostfix])) === false) {
                $ss->error('Не удалось отметить файл %s как потерявший ссылку.');

                return self::FAILURE;
            }

            $ss->note(sprintf('Файл %s отмечен как потреявший ссылку', $dangedFile));
        }

        return self::SUCCESS;
    }

    private function extractSubdir(SplFileInfo $uploadedFile): string
    {
        switch (
            preg_match(
                sprintf('~/upload/(%s)/([a-f0-9]{3})/~', implode('|', $this->usedModules)),
                $uploadedFile->getPathname(),
                $match
            )
        ) {
            case 0:
            case false:
                throw new RuntimeException(
                    sprintf(
                        'Не удалось определить параметры файла %s: %s',
                        $uploadedFile->getPathname(),
                        preg_last_error_msg()
                    )
                );
            default:
                [, $moduleId, $hashStart] = $match;

                if (in_array($moduleId, $this->usedModules) === false) {
                    throw new RuntimeException(
                        sprintf(
                            'Ожидалось, что файл будет находиться в указанном используемом модуле, указан: %s.',
                            $moduleId
                        )
                    );
                }

                if (strlen($hashStart) !== 3) {
                    throw new RuntimeException(
                        sprintf(
                            'Ожидалось, что из пути будет опредён трёхбуквенное начало хеша файла. Получено: %s.',
                            $hashStart
                        )
                    );
                }

                return "$moduleId/$hashStart";
        }
    }
}
