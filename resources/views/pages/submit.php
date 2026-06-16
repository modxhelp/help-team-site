<?php

declare(strict_types=1);

$categories = $viewData['categories'] ?? [];
$csrfToken = (string) ($viewData['csrfToken'] ?? '');
$yandexMapsApiKey = (string) ($viewData['yandexMapsApiKey'] ?? '');
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

<form class="panel form ad-form" method="post" action="/submit" enctype="multipart/form-data" novalidate data-ad-form>
    <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
    <input type="hidden" name="latitude" value="<?= e($value('latitude')) ?>" data-latitude-input>
    <input type="hidden" name="longitude" value="<?= e($value('longitude')) ?>" data-longitude-input>
    <input
        class="honeypot"
        type="text"
        name="website"
        value=""
        tabindex="-1"
        autocomplete="off"
        aria-hidden="true"
        style="position:absolute;left:-10000px;width:1px;height:1px;overflow:hidden;opacity:0;"
    >

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

    <label class="field">
        <span>Кличка собаки</span>
        <input name="dog_name" type="text" value="<?= e($value('dog_name')) ?>" maxlength="255" autocomplete="off">
    </label>

    <label class="field<?= $hasError('body') ? ' field-invalid' : '' ?>">
        <span>Текст объявления <b aria-hidden="true">*</b></span>
        <textarea name="body" required data-autogrow placeholder="Опишите ситуацию, состояние собаки и какая помощь нужна"><?= e($value('body')) ?></textarea>
        <?php if ($hasError('body')): ?>
            <small class="field-error"><?= e($errors['body']) ?></small>
        <?php endif; ?>
    </label>

    <section class="form-section" aria-labelledby="submit-location-title">
        <h2 id="submit-location-title">Место</h2>
        <p class="form-hint">Кликните по карте, чтобы указать место. Город и адрес можно отредактировать вручную.</p>

        <?php if ($yandexMapsApiKey !== ''): ?>
            <div
                class="submit-map"
                id="submit-map"
                data-yandex-map
                data-api-key="<?= e($yandexMapsApiKey) ?>"
                data-latitude="<?= e($value('latitude')) ?>"
                data-longitude="<?= e($value('longitude')) ?>"
            >
                <div class="map-loading">Карта загружается...</div>
            </div>
            <p class="coordinate-hint" data-coordinate-output>
                <?php if ($value('latitude') !== '' && $value('longitude') !== ''): ?>
                    Точка выбрана: <?= e($value('latitude')) ?>, <?= e($value('longitude')) ?>
                <?php else: ?>
                    Точка пока не выбрана.
                <?php endif; ?>
            </p>
        <?php else: ?>
            <div class="map-fallback">
                Карта сейчас не подключена. Форму можно отправить без карты, город и адрес заполните вручную.
            </div>
        <?php endif; ?>

        <div class="form-grid">
            <label class="field">
                <span>Город</span>
                <input name="city" type="text" value="<?= e($value('city')) ?>" maxlength="255" autocomplete="address-level2" data-city-input>
            </label>

            <label class="field">
                <span>Адрес</span>
                <input name="address" type="text" value="<?= e($value('address')) ?>" maxlength="500" autocomplete="street-address" data-address-input>
            </label>
        </div>
    </section>

    <label class="field<?= $hasError('media') ? ' field-invalid' : '' ?>">
        <span>Фото и видео</span>
        <input
            class="file-input"
            name="media[]"
            type="file"
            multiple
            accept="image/jpeg,image/png,image/webp,video/mp4,video/webm,video/quicktime"
        >
        <small class="form-hint">До 10 файлов. Изображения до 8 МБ, видео до 50 МБ. Можно загрузить только одно видео. SVG не принимается.</small>
        <?php if ($hasError('media')): ?>
            <small class="field-error"><?= e($errors['media']) ?></small>
        <?php endif; ?>
    </label>

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
