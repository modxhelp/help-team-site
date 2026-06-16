<?php

declare(strict_types=1);

$ads = $viewData['ads'] ?? [];
$categories = $viewData['categories'] ?? [];
$statuses = $viewData['statuses'] ?? [];
$filters = $viewData['filters'] ?? [
    'category' => '',
    'city' => '',
    'q' => '',
];
$statusLabel = (string) ($statuses['published'] ?? 'опубликовано');
$hasFilters = ($filters['category'] ?? '') !== '' || ($filters['city'] ?? '') !== '' || ($filters['q'] ?? '') !== '';

$excerpt = static function (?string $text, int $limit = 180): string {
    $text = trim(preg_replace('/\s+/u', ' ', $text ?? '') ?? '');

    if ($text === '') {
        return '';
    }

    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        return mb_strlen($text) > $limit ? mb_substr($text, 0, $limit - 1) . '...' : $text;
    }

    return strlen($text) > $limit ? substr($text, 0, $limit - 1) . '...' : $text;
};

$formatDate = static function (?string $date): string {
    if ($date === null || $date === '') {
        return '';
    }

    $timestamp = strtotime($date);

    return $timestamp === false ? '' : date('d.m.Y', $timestamp);
};
?>
<section class="hero">
    <p class="eyebrow">Каталог объявлений</p>
    <h1>Помощь рядом</h1>
    <p class="lead">Опубликованные объявления Help Team: потерялись, найдены, ищут дом или нуждаются в другой поддержке.</p>
    <div class="actions">
        <a class="button button-primary" href="/submit">Подать объявление</a>
        <a class="button button-secondary" href="/map">Открыть карту</a>
    </div>
</section>

<form class="filters panel" method="get" action="/" aria-label="Фильтры каталога">
    <label class="field">
        <span>Категория</span>
        <select name="category">
            <option value="">Все категории</option>
            <?php foreach ($categories as $key => $label): ?>
                <option value="<?= e((string) $key) ?>" <?= ($filters['category'] ?? '') === (string) $key ? 'selected' : '' ?>>
                    <?= e((string) $label) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>

    <label class="field">
        <span>Город</span>
        <input name="city" type="text" value="<?= e((string) ($filters['city'] ?? '')) ?>" placeholder="Например, Симферополь">
    </label>

    <label class="field">
        <span>Поиск</span>
        <input name="q" type="search" value="<?= e((string) ($filters['q'] ?? '')) ?>" placeholder="Кличка, адрес или текст">
    </label>

    <div class="filter-actions">
        <button class="button button-primary" type="submit">Найти</button>
        <?php if ($hasFilters): ?>
            <a class="button button-secondary" href="/">Сбросить</a>
        <?php endif; ?>
    </div>
</form>

<?php if ($ads === []): ?>
    <section class="empty-state panel">
        <h2>Пока нет опубликованных объявлений.</h2>
        <p>Как только модерация опубликует первые объявления, они появятся здесь.</p>
        <a class="button button-primary" href="/submit">Подать объявление</a>
    </section>
<?php else: ?>
    <section class="catalog-grid" aria-label="Объявления">
        <?php foreach ($ads as $ad): ?>
            <?php
            $category = (string) ($ad['category'] ?? '');
            $categoryLabel = (string) ($categories[$category] ?? $category);
            $city = trim((string) ($ad['city'] ?? ''));
            $address = trim((string) ($ad['address'] ?? ''));
            $dogName = trim((string) ($ad['dog_name'] ?? ''));
            $date = $formatDate((string) ($ad['created_at'] ?? ''));
            $text = $excerpt((string) ($ad['body'] ?? ''));
            $imagePath = trim((string) ($ad['image_path'] ?? ''));
            ?>
            <article class="ad-card">
                <?php if ($imagePath !== ''): ?>
                    <img class="ad-card-image" src="<?= e($imagePath) ?>" alt="<?= e($dogName !== '' ? $dogName : $categoryLabel) ?>" loading="lazy">
                <?php else: ?>
                    <div class="ad-card-placeholder" aria-hidden="true">Help Team</div>
                <?php endif; ?>

                <div class="ad-card-body">
                    <div class="ad-card-badges">
                        <span class="badge badge-category"><?= e($categoryLabel) ?></span>
                        <span class="badge badge-status"><?= e($statusLabel) ?></span>
                    </div>

                    <div class="ad-card-meta">
                        <?php if ($city !== ''): ?>
                            <span><?= e($city) ?></span>
                        <?php endif; ?>
                        <?php if ($date !== ''): ?>
                            <span><?= e($date) ?></span>
                        <?php endif; ?>
                    </div>

                    <h2><?= e($dogName !== '' ? $dogName : $categoryLabel) ?></h2>

                    <?php if ($text !== ''): ?>
                        <p><?= e($text) ?></p>
                    <?php endif; ?>

                    <?php if ($address !== ''): ?>
                        <div class="ad-card-address"><?= e($address) ?></div>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>
