# maximaster/bitrix-cli-commands

Консольные команды для работы с Битрикс совместимые с `symfony/console`.

* `bitrix:cache:clear` - удалить кеш.

## Подключение

```bash
composer require maximaster/bitrix-cli-commands
```

В вашем `bin/console` добавьте команды:

```php
$app->addCommands(require __DIR__ . '/../vendor/maximaster/bitrix-cli-commands/config/commands.php');
```

Или создайте экземпляры нужных команд и добавьте их вручную.
