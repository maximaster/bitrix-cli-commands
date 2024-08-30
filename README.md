# maximaster/bitrix-cli-commands

Консольные команды для работы с Битрикс совместимые с `symfony/console`.

* `main` модуль:
  * `bitrix:cache:clear` - удалить кеш;
  * `bitrix:main:mark-dangled-files` - удалить неиспользуемые файлы из b_file;
* `im` модуль:
  * `bitrix:im:delete-chats` - удалить чаты, кроме указанных;
  * `bitrix:im:delete-messages` - удалить все сообщения из чатов.

## Подключение

```bash
composer require maximaster/bitrix-cli-commands
```

В вашем `bin/console` добавьте команды:

```php
$bitrixLoader = \Maximaster\BitrixLoader\BitrixLoader::fromComposerConfigExtra(__DIR__ . '/../composer.json');
$bitrixCliCommandsFactory = require __DIR__ . '/../vendor/maximaster/bitrix-cli-commands/config/commands.php';
$app->addCommands($bitrixCliCommandsFactory($bitrixLoader));
```

Или вместо `require` создайте экземпляры нужных команд по его подобию и добавьте
их вручную.
