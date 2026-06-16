<?php

declare(strict_types=1);
?>
<section class="hero">
    <p class="eyebrow">Подать объявление</p>
    <h1>Расскажите, какая помощь нужна</h1>
    <p class="lead">Форма пока работает как заглушка, но уже показывает будущую структуру подачи объявления.</p>
</section>

<form class="panel form" data-submit-placeholder>
    <label class="field">
        <span>Название</span>
        <input name="title" type="text" placeholder="Например, щенку нужна передержка">
    </label>
    <label class="field">
        <span>Категория</span>
        <select name="category">
            <option>Нужен дом</option>
            <option>Лечение</option>
            <option>Передержка</option>
            <option>Другая помощь</option>
        </select>
    </label>
    <label class="field">
        <span>Описание</span>
        <textarea name="description" placeholder="Коротко опишите ситуацию"></textarea>
    </label>
    <label class="field">
        <span>Город или адрес</span>
        <input name="location" type="text" placeholder="Москва, район или улица">
    </label>
    <button class="button button-primary" type="submit">Отправить</button>
    <p data-form-status aria-live="polite"></p>
</form>
