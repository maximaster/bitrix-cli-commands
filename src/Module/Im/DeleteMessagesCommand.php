<?php

declare(strict_types=1);

namespace Maximaster\BitrixCliCommands\Module\Im;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use CIMMessenger;
use Maximaster\BitrixEnums\Main\Orm\OrderDirection;
use Maximaster\BitrixTableClasses\Table\Im\MessageTable;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Очищает сообщения чатов.
 */
class DeleteMessagesCommand extends Command
{
    protected static $defaultName = 'bitrix:im:delete-messages';

    /**
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ss = new SymfonyStyle($input, $output);

        CIMMessenger::DisableMessageCheck();

        $res = MessageTable::query()
            ->setSelect([MessageTable::ID])
            ->setOrder([MessageTable::ID => OrderDirection::DESC])
            ->exec();

        $progress = $ss->createProgressBar($res->getSelectedRowsCount());
        while ($message = $res->fetch()) {
            $deleted = CIMMessenger::Delete($message[MessageTable::ID], null, true);
            if ($deleted === false) {
                $ss->error(sprintf('Не удалось удалить сообщение %s', $message[MessageTable::ID]));

                return self::FAILURE;
            }

            $progress->advance();
            $progress->setMessage(sprintf('Сообщение %d удалено', $message[MessageTable::ID]));
        }
        $progress->finish();
        $output->write(PHP_EOL);

        $ss->success(sprintf('Удалено сообщений: %d', $res->getSelectedRowsCount()));

        return self::SUCCESS;
    }
}
