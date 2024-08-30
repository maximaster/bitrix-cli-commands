<?php

declare(strict_types=1);

namespace Maximaster\BitrixCliCommands\Module\Im;

use Bitrix\Disk\Folder;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Storage;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\NotImplementedException;
use CIMChat;
use Maximaster\BitrixEnums\Main\Orm\OrderDirection;
use Maximaster\BitrixEnums\Main\UserId;
use Maximaster\BitrixTableClasses\Table\Im\ChatTable;
use Maximaster\BitrixUnstatic\Contract\Main\Application;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Удаляет чаты.
 */
class DeleteChatsCommand extends Command
{
    private const OPT_EXCLUDE = 'exclude';
    private const OPT_DRY_RUN = 'dry-run';

    protected static $defaultName = 'bitrix:im:delete-chats';

    private Application $application;

    public function __construct(Application $application)
    {
        parent::__construct();

        $this->application = $application;
    }

    protected function configure(): void
    {
        $this->addOption(
            self::OPT_EXCLUDE,
            null,
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'ID чатов которые нужно оставить'
        );
        $this->addOption(
            self::OPT_DRY_RUN,
            null,
            InputOption::VALUE_NONE,
            'Не производить удаления, вывести отчёт.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ss = new SymfonyStyle($input, $output);

        $dryRun = (bool) $input->getOption(self::OPT_DRY_RUN);

        $excludedIds = $input->getOption(self::OPT_EXCLUDE);

        $allExcludedIsNumeric = $excludedIds === array_map(
            static fn ($excludedId) => is_numeric($excludedId),
            $excludedIds
        );

        if ($allExcludedIsNumeric === false) {
            $ss->error('Исключения должны быть идентификаторами чатов');

            return self::FAILURE;
        }

        if (count($excludedIds) === 0) {
            $ss->error('Установите исключения через --exclude. Если они не нужны, укажите 0.');

            return self::FAILURE;
        }

        $params = [
            'select' => [ChatTable::ID, ChatTable::DISK_FOLDER_ID],
            // От новых к старым
            'order' => [ChatTable::ID => OrderDirection::DESC],
        ];
        if (is_array($excludedIds)) {
            $params['filter'] = ['!=' . ChatTable::ID => $excludedIds];
        }

        $ss->note(sprintf('Удаляем чаты с параметрами: %s', json_encode($params)));
        $res = ChatTable::getList($params);
        $progress = $ss->createProgressBar($res->getSelectedRowsCount());
        while ($chat = $res->fetch()) {
            if ($dryRun === false) {
                $this->deleteChat(
                    (int) $chat[ChatTable::ID],
                    $chat[ChatTable::DISK_FOLDER_ID] === null ? null : (int) $chat[ChatTable::DISK_FOLDER_ID]
                );
            }

            $progress->advance();
            $progress->setMessage(sprintf('Удалён чат %d', $chat[ChatTable::ID]));
        }
        $progress->finish();
        $output->write(PHP_EOL);

        $ss->success('Удаление завершено');

        return self::SUCCESS;
    }

    /**
     * @throws SqlQueryException
     * @throws NotImplementedException
     */
    private function deleteChat(int $chatId, ?int $chatFolderId): void
    {
        if ($chatId <= 0) {
            throw new RuntimeException('Некорректный идентификатор чата %s');
        }

        if (is_int($chatFolderId) && $chatFolderId <= 0) {
            throw new RuntimeException('Некорректный идентификатор папки чата %s');
        }

        $chatFolder = Folder::loadById($chatFolderId);
        if ($chatFolder instanceof Folder) {
            $chatStorage = $chatFolder->getStorage();
            if ($chatStorage instanceof Storage) {
                $this->deleteChatStorage($chatStorage);
            }
        }

        CIMChat::hide($chatId);

        $connection = $this->application->getConnection();
        $connection->query(sprintf('DELETE FROM b_im_relation WHERE CHAT_ID = %d', $chatId));
        $connection->query(sprintf('DELETE FROM b_im_message WHERE CHAT_ID = %d', $chatId));
        $connection->query(sprintf('DELETE FROM b_im_chat WHERE ID = %d', $chatId));
    }

    private function deleteChatStorage(Storage $chatStorage): void
    {
        $deleted = $chatStorage->delete(UserId::ADMIN);
        if ($deleted === false) {
            throw new RuntimeException(
                implode('; ', array_map(static fn (Error $error) => $error->getMessage(), $chatStorage->getErrors()))
            );
        }
    }
}
