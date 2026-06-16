# Help Team site — правила для Codex

Проект: сайт объявлений помощи собакам Help Team.

Локальная рабочая папка:

C:\help-team-site

Структура локально:

- app
- bootstrap
- config
- database
- resources
- storage
- public_html

На хостинге Beget проект раскладывается так:

- public_html/* → /home/w/wm48mav4/wm48mav4.beget.tech/public_html
- остальная закрытая часть → /home/w/wm48mav4/wm48mav4.beget.tech/help-team-site

Публично доступные файлы должны лежать только в public_html.

Нельзя класть в public_html:

- .env
- vendor
- composer.json
- composer.lock
- app
- config
- database
- storage
- resources
- приватные файлы

Техническая конфигурация хостинга:

- PHP CLI: /usr/local/bin/php8.5
- Web PHP: 8.5.2
- DB_HOST: localhost
- DB_NAME: wm48mav4_helptm
- DB_USER: wm48mav4_helptm

Стек проекта:

- PHP 8.5
- MySQL
- RedBeanPHP
- HTML5
- современный CSS
- Vanilla JS / ES Modules
- без CMS
- без Laravel, Symfony, Yii
- без jQuery
- без React/Vue на старте

Основные разделы сайта:

1. Главная — каталог объявлений.
2. Подать объявление — форма подачи объявления с картой.
3. Карта объявлений — карта с метками и кластеризацией.
4. О нас — пока заглушка.

После изменений проверять PHP-синтаксис командой:

php -l путь/к/файлу.php

Если локально нет PHP 8.5, синтаксис проверять после деплоя на сервере через:

/usr/local/bin/php8.5 -l путь/к/файлу.php
