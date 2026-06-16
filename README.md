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
YANDEX_MAPS_API_KEY=
YANDEX_GEOCODER_API_KEY=
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

## Карта и геокодер

На странице подачи объявления карта подключается только при наличии ключа:

```dotenv
YANDEX_MAPS_API_KEY=
```

Обратное геокодирование выполняется серверным endpoint `POST /api/geocode/reverse`.
Ключ геокодера хранится только в `.env` и не передается во frontend:

```dotenv
YANDEX_GEOCODER_API_KEY=
```

Если `YANDEX_MAPS_API_KEY` не указан, форма работает без карты: город и адрес можно заполнить вручную.

## Загрузка медиа

Форма `/submit` принимает поле `media[]` с несколькими файлами.

Разрешены:

- `image/jpeg`
- `image/png`
- `image/webp`
- `video/mp4`
- `video/webm`
- `video/quicktime`

Ограничения:

- максимум 10 файлов
- изображение до 8 МБ
- видео до 50 МБ
- максимум 1 видео
- SVG запрещен
- MIME проверяется на сервере через PHP `finfo_file`

Файлы сохраняются в публичной части:

```text
public_html/uploads/ads/YYYY/MM/{ad_id}/filename.ext
```

В базе хранится относительный путь:

```text
/uploads/ads/2026/06/123/file.webp
```

После добавления или обновления таблиц нужно запускать миграции:

```bash
php database/migrate.php
```
