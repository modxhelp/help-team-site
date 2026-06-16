<?php

declare(strict_types=1);

namespace HelpTeam\Service;

use HelpTeam\Support\Logger;
use RuntimeException;

final class MediaUploadService
{
    private const MAX_FILES = 10;
    private const MAX_IMAGE_SIZE = 8 * 1024 * 1024;
    private const MAX_VIDEO_SIZE = 50 * 1024 * 1024;

    /**
     * @var array<string, array{type: string, extension: string, max_size: int}>
     */
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg' => ['type' => 'image', 'extension' => 'jpg', 'max_size' => self::MAX_IMAGE_SIZE],
        'image/png' => ['type' => 'image', 'extension' => 'png', 'max_size' => self::MAX_IMAGE_SIZE],
        'image/webp' => ['type' => 'image', 'extension' => 'webp', 'max_size' => self::MAX_IMAGE_SIZE],
        'video/mp4' => ['type' => 'video', 'extension' => 'mp4', 'max_size' => self::MAX_VIDEO_SIZE],
        'video/webm' => ['type' => 'video', 'extension' => 'webm', 'max_size' => self::MAX_VIDEO_SIZE],
        'video/quicktime' => ['type' => 'video', 'extension' => 'mov', 'max_size' => self::MAX_VIDEO_SIZE],
    ];

    public function __construct(
        private readonly string $publicPath
    ) {
    }

    /**
     * @param array<string, mixed>|null $files
     * @return array{items: list<array<string, int|string>>, errors: list<string>, has_uploads: bool}
     */
    public function validate(?array $files): array
    {
        Logger::info('media.validate.input', [
            'has_media_payload' => $files !== null,
            'payload_keys' => is_array($files) ? array_keys($files) : [],
            'name_is_array' => isset($files['name']) && is_array($files['name']),
        ]);

        if ($files !== null && !$this->hasExpectedUploadKeys($files)) {
            Logger::warning('media.validate.malformed_payload', [
                'payload_keys' => array_keys($files),
            ]);

            return [
                'items' => [],
                'errors' => ['Не удалось прочитать загруженные файлы. Попробуйте выбрать файлы еще раз.'],
                'has_uploads' => true,
            ];
        }

        $uploads = $this->normalizeFiles($files);
        $errors = [];
        $items = [];
        $videoCount = 0;
        $hasUploads = $this->hasRealUploads($uploads);

        Logger::info('media.validate.normalized', [
            'count' => count($uploads),
            'has_uploads' => $hasUploads,
            'uploads' => $this->uploadsForLog($uploads),
        ]);

        if (count($uploads) > self::MAX_FILES) {
            return [
                'items' => [],
                'errors' => ['Можно загрузить не больше 10 файлов.'],
                'has_uploads' => $hasUploads,
            ];
        }

        if (!$hasUploads) {
            return [
                'items' => [],
                'errors' => [],
                'has_uploads' => false,
            ];
        }

        if (!class_exists(\finfo::class)) {
            Logger::error('media.validate.fileinfo_missing');

            return [
                'items' => [],
                'errors' => ['На сервере недоступна проверка MIME-файлов. Обратитесь к администратору сайта.'],
                'has_uploads' => true,
            ];
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);

        foreach ($uploads as $index => $upload) {
            $fileNumber = $index + 1;
            $error = (int) $upload['error'];

            Logger::info('media.validate.upload', [
                'index' => $index,
                'upload_error' => $error,
                'tmp_name' => (string) $upload['tmp_name'],
                'size' => (int) $upload['size'],
            ]);

            if ($error === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            if ($error !== UPLOAD_ERR_OK) {
                $errors[] = "Файл {$fileNumber}: загрузка не удалась.";
                continue;
            }

            $tmpName = (string) $upload['tmp_name'];

            if (!is_uploaded_file($tmpName)) {
                Logger::warning('media.validate.not_uploaded_file', [
                    'index' => $index,
                    'tmp_name' => $tmpName,
                ]);

                $errors[] = "Файл {$fileNumber}: некорректная загрузка.";
                continue;
            }

            $mimeType = $finfo->file($tmpName) ?: '';
            Logger::info('media.validate.mime', [
                'index' => $index,
                'tmp_name' => $tmpName,
                'mime_type' => $mimeType,
            ]);

            $rules = self::ALLOWED_MIME_TYPES[$mimeType] ?? null;

            if ($rules === null) {
                $errors[] = "Файл {$fileNumber}: разрешены только JPG, PNG, WebP, MP4, WebM и MOV.";
                continue;
            }

            $size = (int) $upload['size'];

            if ($size > $rules['max_size']) {
                $errors[] = $rules['type'] === 'video'
                    ? "Файл {$fileNumber}: видео должно быть не больше 50 МБ."
                    : "Файл {$fileNumber}: изображение должно быть не больше 8 МБ.";
                continue;
            }

            if ($rules['type'] === 'video') {
                $videoCount++;

                if ($videoCount > 1) {
                    $errors[] = 'Можно загрузить только одно видео.';
                    continue;
                }
            }

            $items[] = [
                'tmp_name' => $tmpName,
                'original_name' => $this->cleanOriginalName((string) $upload['name']),
                'mime_type' => $mimeType,
                'file_size' => $size,
                'media_type' => $rules['type'],
                'extension' => $rules['extension'],
                'sort_order' => count($items),
            ];
        }

        return [
            'items' => $errors === [] ? $items : [],
            'errors' => $errors,
            'has_uploads' => $hasUploads,
        ];
    }

    /**
     * @param list<array<string, int|string>> $items
     * @return list<array<string, int|string>>
     */
    public function store(int $adId, array $items): array
    {
        Logger::info('media.store.start', [
            'ad_id' => $adId,
            'items_count' => count($items),
            'public_path' => $this->publicPath,
        ]);

        if ($items === []) {
            return [];
        }

        $year = date('Y');
        $month = date('m');
        $relativeDir = "/uploads/ads/{$year}/{$month}/{$adId}";
        $targetDir = rtrim($this->publicPath, '/\\') . str_replace('/', DIRECTORY_SEPARATOR, $relativeDir);

        Logger::info('media.store.target_dir', [
            'ad_id' => $adId,
            'target_dir' => $targetDir,
        ]);

        if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            Logger::error('media.store.mkdir_failed', [
                'ad_id' => $adId,
                'target_dir' => $targetDir,
            ]);

            throw new RuntimeException('Не удалось создать папку для загрузки файлов.');
        }

        $stored = [];

        foreach ($items as $item) {
            $fileName = bin2hex(random_bytes(16)) . '.' . $item['extension'];
            $targetPath = $targetDir . DIRECTORY_SEPARATOR . $fileName;

            Logger::info('media.store.target_path', [
                'ad_id' => $adId,
                'sort_order' => (int) $item['sort_order'],
                'tmp_name' => (string) $item['tmp_name'],
                'target_path' => $targetPath,
            ]);

            if (!move_uploaded_file((string) $item['tmp_name'], $targetPath)) {
                Logger::error('media.store.move_failed', [
                    'ad_id' => $adId,
                    'tmp_name' => (string) $item['tmp_name'],
                    'target_path' => $targetPath,
                    'target_dir_writable' => is_writable($targetDir),
                ]);

                $this->deleteStored($stored);

                throw new RuntimeException('Не удалось сохранить загруженный файл.');
            }

            $stored[] = [
                'media_type' => (string) $item['media_type'],
                'file_path' => "{$relativeDir}/{$fileName}",
                'original_name' => (string) $item['original_name'],
                'mime_type' => (string) $item['mime_type'],
                'file_size' => (int) $item['file_size'],
                'sort_order' => (int) $item['sort_order'],
            ];
        }

        Logger::info('media.store.done', [
            'ad_id' => $adId,
            'stored_count' => count($stored),
            'paths' => array_map(static fn (array $item): string => (string) $item['file_path'], $stored),
        ]);

        return $stored;
    }

    /**
     * @param list<array<string, int|string>> $items
     */
    public function deleteStored(array $items): void
    {
        foreach ($items as $item) {
            $relativePath = (string) ($item['file_path'] ?? '');

            if ($relativePath === '' || !str_starts_with($relativePath, '/uploads/ads/')) {
                continue;
            }

            $path = rtrim($this->publicPath, '/\\') . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    /**
     * @param array<string, mixed>|null $files
     * @return list<array{name: string, tmp_name: string, size: int, error: int}>
     */
    private function normalizeFiles(?array $files): array
    {
        if ($files === null || !isset($files['name'], $files['tmp_name'], $files['size'], $files['error'])) {
            return [];
        }

        if (!is_array($files['name'])) {
            return [[
                'name' => (string) $files['name'],
                'tmp_name' => (string) $files['tmp_name'],
                'size' => (int) $files['size'],
                'error' => (int) $files['error'],
            ]];
        }

        $uploads = [];

        foreach ($files['name'] as $index => $name) {
            $uploads[] = [
                'name' => (string) $name,
                'tmp_name' => (string) ($files['tmp_name'][$index] ?? ''),
                'size' => (int) ($files['size'][$index] ?? 0),
                'error' => (int) ($files['error'][$index] ?? UPLOAD_ERR_NO_FILE),
            ];
        }

        return $uploads;
    }

    /**
     * @param array<string, mixed> $files
     */
    private function hasExpectedUploadKeys(array $files): bool
    {
        return isset($files['name'], $files['tmp_name'], $files['size'], $files['error']);
    }

    private function cleanOriginalName(string $name): string
    {
        $name = trim(str_replace(["\r", "\n", "\0"], '', $name));

        if ($name === '') {
            return 'file';
        }

        return function_exists('mb_substr')
            ? mb_substr($name, 0, 255)
            : substr($name, 0, 255);
    }

    /**
     * @param list<array{name: string, tmp_name: string, size: int, error: int}> $uploads
     */
    private function hasRealUploads(array $uploads): bool
    {
        foreach ($uploads as $upload) {
            if ((int) $upload['error'] !== UPLOAD_ERR_NO_FILE) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<array{name: string, tmp_name: string, size: int, error: int}> $uploads
     * @return list<array{index: int, error: int, tmp_name: string, size: int}>
     */
    private function uploadsForLog(array $uploads): array
    {
        $log = [];

        foreach ($uploads as $index => $upload) {
            $log[] = [
                'index' => $index,
                'error' => (int) $upload['error'],
                'tmp_name' => (string) $upload['tmp_name'],
                'size' => (int) $upload['size'],
            ];
        }

        return $log;
    }
}
