<?php

declare(strict_types=1);

$viewPath = __DIR__ . '/pages/' . $view . '.php';
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> | Help Team</title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <script type="module" src="/assets/js/app.js"></script>
</head>
<body>
    <header class="site-header">
        <div class="container topbar">
            <a class="brand" href="/">
                <span class="brand-mark">HT</span>
                <span>Help Team</span>
            </a>
            <nav class="nav" aria-label="Основная навигация">
                <a href="/">Каталог</a>
                <a href="/submit">Подать объявление</a>
                <a href="/map">Карта</a>
                <a href="/about">О нас</a>
            </nav>
        </div>
    </header>

    <main class="page">
        <div class="container">
            <?php require $viewPath; ?>
        </div>
    </main>

    <footer class="site-footer">
        <div class="container">
            Help Team помогает быстрее находить тех, кому нужна забота.
        </div>
    </footer>
</body>
</html>
