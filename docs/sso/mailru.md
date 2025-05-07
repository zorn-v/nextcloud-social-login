# How to Setup Mail.ru SSO

To set up login via Mail.ru for your Nextcloud instance, follow these steps:

* Create a Mail.ru account if you don’t already have one (https://account.mail.ru/)
* Add a new site in your Mail.ru account:
    * Go to https://api.mail.ru/sites/my/add
    * In the "Address" field, enter the URL of your Nextcloud main page (e.g., https://your-nextcloud-domain.com)
    * Download the `_receiver.html` file, rename it to `receiver.html`, and upload it to the root directory of your Nextcloud site (e.g., `/var/www/nextcloud/`)
    * Save the provided **ID** and **Secret Key** for later use
* Configure the Social Login plugin in Nextcloud:
    * In the plugin settings, locate the configuration fields
    * Enter the **ID** in the `App ID` field
    * Enter the **Secret Key** in the `Secret` field

# Как настроить Mail.ru SSO

Для настройки входа через Mail.ru в вашем экземпляре Nextcloud выполните следующие шаги:

* Создайте учетную запись Mail.ru, если у вас ее еще нет (https://account.mail.ru/)
* Добавьте новый сайт в вашей учетной записи Mail.ru:
    * Перейдите по адресу https://api.mail.ru/sites/my/add
    * В поле «Адрес» укажите URL главной страницы вашего Nextcloud (например, https://your-nextcloud-domain.com)
    * Скачайте файл `_receiver.html`, переименуйте его в `receiver.html` и загрузите в корневую папку вашего сайта Nextcloud (например, `/var/www/nextcloud/`)
    * Сохраните предоставленные **ID** и **Секретный ключ** для последующего использования
* Настройте плагин Social Login в Nextcloud:
    * В настройках плагина найдите поля конфигурации
    * Введите **ID** в поле `Идентификатор приложения`
    * Введите **Секретный ключ** в поле `Секрет`