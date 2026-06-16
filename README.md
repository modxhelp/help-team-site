# Help Team

Простой сайт объявлений для помощи собакам.

## Стек

- PHP 8.5
- MySQL
- RedBeanPHP
- HTML5
- CSS
- Vanilla JS

## Структура

- `public_html/` — публичная часть сайта.
- `app/` — код приложения.
- `bootstrap/` — загрузка приложения.
- `config/` — конфиги.
- `database/` — миграции.
- `resources/` — шаблоны.
- `storage/` — логи, кэш, временные файлы.

## Хостинг

Публичная часть:

`/home/w/wm48mav4/wm48mav4.beget.tech/public_html`

Закрытая часть:

`/home/w/wm48mav4/wm48mav4.beget.tech/help-team-site`

## Миграции базы данных

Миграции лежат в `database/migrations/` и запускаются через простой CLI-мигратор:

```bash
php database/migrate.php
```

Мигратор создает таблицу `migrations` и не применяет повторно уже выполненные файлы.

Для подключения к базе нужен `.env` в корне закрытой части проекта. Реальные пароли не хранятся в Git, поэтому сначала скопируйте пример и заполните значения:

```bash
cp .env.example .env
```

Нужные переменные:

```dotenv
DB_HOST=localhost
DB_PORT=3306
DB_NAME=help_team
DB_USER=help_team
DB_PASS=
DB_CHARSET=utf8mb4
```

Локально:

```bash
php database/migrate.php
```

На Beget запускать из закрытой части проекта:

```bash
cd /home/w/wm48mav4/wm48mav4.beget.tech/help-team-site
/usr/local/bin/php8.5 database/migrate.php
```
