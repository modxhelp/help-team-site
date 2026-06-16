<?php

declare(strict_types=1);

$viewPath = __DIR__ . '/pages/' . $view . '.php';
$viewData = $viewData ?? [];
$messages = $viewData['messages'] ?? [];

if (isset($viewData['errors']['form'])) {
    $messages[] = [
        'type' => 'error',
        'text' => $viewData['errors']['form'],
    ];
}
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
            <?php if ($messages !== []): ?>
                <div class="messages" role="status" aria-live="polite">
                    <?php foreach ($messages as $message): ?>
                        <div class="message message-<?= e($message['type'] ?? 'info') ?>">
                            <?= e($message['text'] ?? '') ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

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
