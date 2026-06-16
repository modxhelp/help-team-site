<?php

declare(strict_types=1);

namespace HelpTeam\Controller;

use HelpTeam\Repository\AdRepository;
use HelpTeam\Service\MediaUploadService;
use Throwable;

final class SubmitAdController
{
    private const SUCCESS_MESSAGE = 'Спасибо! Объявление отправлено на модерацию.';

    /**
     * @param array<string, string> $categories
     */
    public function __construct(
        private readonly AdRepository $ads,
        private readonly MediaUploadService $media,
        private readonly array $categories,
        private readonly string $yandexMapsApiKey = ''
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function show(): array
    {
        return $this->formData(messages: $this->pullMessages());
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $files
     * @return array<string, mixed>
     */
    public function submit(array $input, array $files): array
    {
        $old = $this->normalizeInput($input);

        if ($this->honeypotFilled($input)) {
            $this->flash('success', self::SUCCESS_MESSAGE);

            return ['redirect' => '/submit'];
        }

        $errors = $this->validate($old, (string) ($input['_token'] ?? ''));
        $mediaValidation = $this->media->validate($files['media'] ?? null);

        if ($mediaValidation['errors'] !== []) {
            $errors['media'] = implode(' ', $mediaValidation['errors']);
        }

        if ($errors !== []) {
            return $this->formData($old, $errors);
        }

        $storedMedia = [];
        $transactionStarted = false;

        try {
            $this->ads->begin();
            $transactionStarted = true;

            $adId = $this->ads->create([
                'category' => $old['category'],
                'dog_name' => $this->nullable($old['dog_name']),
                'title' => null,
                'body' => $old['body'],
                'city' => $this->nullable($old['city']),
                'address' => $this->nullable($old['address']),
                'latitude' => $this->coordinate($old['latitude'], -90, 90),
                'longitude' => $this->coordinate($old['longitude'], -180, 180),
                'contact_name' => $this->nullable($old['contact_name']),
                'contact_phone' => $this->nullable($old['contact_phone']),
                'contact_vk' => $this->nullable($old['contact_vk']),
            ]);

            $storedMedia = $this->media->store($adId, $mediaValidation['items']);
            $this->ads->createMedia($adId, $storedMedia);

            $this->ads->commit();
        } catch (Throwable) {
            if ($transactionStarted) {
                try {
                    $this->ads->rollback();
                } catch (Throwable) {
                }
            }

            $this->media->deleteStored($storedMedia);

            return $this->formData($old, [
                'form' => 'Не удалось сохранить объявление. Проверьте подключение к базе и попробуйте еще раз.',
            ]);
        }

        $this->rotateCsrfToken();
        $this->flash('success', self::SUCCESS_MESSAGE);

        return ['redirect' => '/submit'];
    }

    /**
     * @param array<string, string> $old
     * @param array<string, string> $errors
     * @param list<array{type: string, text: string}> $messages
     * @return array<string, mixed>
     */
    private function formData(array $old = [], array $errors = [], array $messages = []): array
    {
        return [
            'categories' => $this->categories,
            'csrfToken' => $this->csrfToken(),
            'yandexMapsApiKey' => $this->yandexMapsApiKey,
            'old' => array_merge($this->emptyOldInput(), $old),
            'errors' => $errors,
            'messages' => $messages,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function emptyOldInput(): array
    {
        return [
            'category' => '',
            'dog_name' => '',
            'body' => '',
            'city' => '',
            'address' => '',
            'latitude' => '',
            'longitude' => '',
            'contact_name' => '',
            'contact_phone' => '',
            'contact_vk' => '',
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, string>
     */
    private function normalizeInput(array $input): array
    {
        return [
            'category' => $this->clean($input['category'] ?? '', 50),
            'dog_name' => $this->clean($input['dog_name'] ?? '', 255),
            'body' => $this->clean($input['body'] ?? ''),
            'city' => $this->clean($input['city'] ?? '', 255),
            'address' => $this->clean($input['address'] ?? '', 500),
            'latitude' => $this->clean($input['latitude'] ?? '', 32),
            'longitude' => $this->clean($input['longitude'] ?? '', 32),
            'contact_name' => $this->clean($input['contact_name'] ?? '', 255),
            'contact_phone' => $this->clean($input['contact_phone'] ?? '', 100),
            'contact_vk' => $this->clean($input['contact_vk'] ?? '', 255),
        ];
    }

    /**
     * @param array<string, string> $old
     * @return array<string, string>
     */
    private function validate(array $old, string $token): array
    {
        $errors = [];

        if (!hash_equals($this->csrfToken(), $token)) {
            $errors['form'] = 'Сессия формы истекла. Обновите страницу и отправьте объявление еще раз.';
        }

        if ($old['category'] === '') {
            $errors['category'] = 'Выберите категорию объявления.';
        } elseif (!isset($this->categories[$old['category']])) {
            $errors['category'] = 'Выбрана неизвестная категория.';
        }

        if ($old['body'] === '') {
            $errors['body'] = 'Опишите ситуацию в тексте объявления.';
        }

        return $errors;
    }

    private function clean(mixed $value, ?int $maxLength = null): string
    {
        $value = str_replace(["\r\n", "\r"], "\n", (string) $value);
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? $value;
        $value = trim($value);

        if ($maxLength !== null) {
            return function_exists('mb_substr')
                ? mb_substr($value, 0, $maxLength)
                : substr($value, 0, $maxLength);
        }

        return $value;
    }

    private function nullable(string $value): ?string
    {
        return $value === '' ? null : $value;
    }

    private function coordinate(mixed $value, float $min, float $max): ?float
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        $number = filter_var($value, FILTER_VALIDATE_FLOAT);

        if ($number === false || $number < $min || $number > $max) {
            return null;
        }

        return (float) $number;
    }

    /**
     * @param array<string, mixed> $input
     */
    private function honeypotFilled(array $input): bool
    {
        return trim((string) ($input['website'] ?? '')) !== '';
    }

    private function csrfToken(): string
    {
        if (empty($_SESSION['_csrf_token']) || !is_string($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf_token'];
    }

    private function rotateCsrfToken(): void
    {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    private function flash(string $type, string $text): void
    {
        $_SESSION['_flash'][] = [
            'type' => $type,
            'text' => $text,
        ];
    }

    /**
     * @return list<array{type: string, text: string}>
     */
    private function pullMessages(): array
    {
        $messages = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);

        return is_array($messages) ? $messages : [];
    }
}
