<?php

declare(strict_types=1);

$categories = $viewData['categories'] ?? [];
$csrfToken = (string) ($viewData['csrfToken'] ?? '');
$old = $viewData['old'] ?? [];
$errors = $viewData['errors'] ?? [];
$value = static fn (string $key): string => (string) ($old[$key] ?? '');
$hasError = static fn (string $key): bool => isset($errors[$key]);
?>
<section class="hero">
    <p class="eyebrow">Подать объявление</p>
    <h1>Расскажите, какая помощь нужна</h1>
    <p class="lead">Заполните объявление. После отправки оно попадет на модерацию и появится в каталоге после проверки.</p>
</section>

<form class="panel form ad-form" method="post" action="/submit" novalidate data-ad-form>
    <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
    <input type="hidden" name="latitude" value="">
    <input type="hidden" name="longitude" value="">

    <div class="honeypot" aria-hidden="true">
        <label>
            Сайт
            <input type="text" name="website" tabindex="-1" autocomplete="off">
        </label>
    </div>

    <label class="field<?= $hasError('category') ? ' field-invalid' : '' ?>">
        <span>Категория <b aria-hidden="true">*</b></span>
        <select name="category" required>
            <option value="">Выберите категорию</option>
            <?php foreach ($categories as $key => $label): ?>
                <option value="<?= e((string) $key) ?>" <?= $value('category') === (string) $key ? 'selected' : '' ?>>
                    <?= e((string) $label) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if ($hasError('category')): ?>
            <small class="field-error"><?= e($errors['category']) ?></small>
        <?php endif; ?>
    </label>

    <div class="form-grid">
        <label class="field">
            <span>Кличка собаки</span>
            <input name="dog_name" type="text" value="<?= e($value('dog_name')) ?>" maxlength="255" autocomplete="off">
        </label>

        <label class="field">
            <span>Короткий заголовок</span>
            <input name="title" type="text" value="<?= e($value('title')) ?>" maxlength="255" placeholder="Например, нужна передержка на неделю">
        </label>
    </div>

    <label class="field<?= $hasError('body') ? ' field-invalid' : '' ?>">
        <span>Текст объявления <b aria-hidden="true">*</b></span>
        <textarea name="body" required data-autogrow placeholder="Опишите ситуацию, состояние собаки и какая помощь нужна"><?= e($value('body')) ?></textarea>
        <?php if ($hasError('body')): ?>
            <small class="field-error"><?= e($errors['body']) ?></small>
        <?php endif; ?>
    </label>

    <div class="form-grid">
        <label class="field">
            <span>Город</span>
            <input name="city" type="text" value="<?= e($value('city')) ?>" maxlength="255" autocomplete="address-level2">
        </label>

        <label class="field">
            <span>Адрес</span>
            <input name="address" type="text" value="<?= e($value('address')) ?>" maxlength="500" autocomplete="street-address">
        </label>
    </div>

    <div class="form-grid">
        <label class="field">
            <span>Контактное лицо</span>
            <input name="contact_name" type="text" value="<?= e($value('contact_name')) ?>" maxlength="255" autocomplete="name">
        </label>

        <label class="field">
            <span>Телефон</span>
            <input name="contact_phone" type="tel" value="<?= e($value('contact_phone')) ?>" maxlength="100" autocomplete="tel">
        </label>
    </div>

    <label class="field">
        <span>ВК или другой контакт</span>
        <input name="contact_vk" type="text" value="<?= e($value('contact_vk')) ?>" maxlength="255" placeholder="https://vk.com/... или @username">
    </label>

    <div class="form-actions">
        <button class="button button-primary" type="submit">Отправить на модерацию</button>
    </div>
</form>
