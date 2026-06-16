<?php

declare(strict_types=1);

namespace HelpTeam\Controller;

final class ReverseGeocodeController
{
    public function __construct(
        private readonly string $apiKey
    ) {
    }

    /**
     * @return array{status: int, payload: array<string, mixed>}
     */
    public function handle(string $rawBody): array
    {
        $data = json_decode($rawBody, true);

        if (!is_array($data)) {
            return $this->response(false, 'Некорректный JSON.', 400);
        }

        $latitude = filter_var($data['latitude'] ?? null, FILTER_VALIDATE_FLOAT);
        $longitude = filter_var($data['longitude'] ?? null, FILTER_VALIDATE_FLOAT);

        if ($latitude === false || $longitude === false || $latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            return $this->response(false, 'Некорректные координаты.', 422);
        }

        if ($this->apiKey === '') {
            return $this->response(false, 'Адрес не удалось определить автоматически.');
        }

        $query = http_build_query([
            'apikey' => $this->apiKey,
            'format' => 'json',
            'geocode' => $longitude . ',' . $latitude,
            'results' => 1,
        ]);

        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents('https://geocode-maps.yandex.ru/1.x/?' . $query, false, $context);

        if ($response === false) {
            return $this->response(false, 'Адрес не удалось определить автоматически.');
        }

        $decoded = json_decode($response, true);

        if (!is_array($decoded)) {
            return $this->response(false, 'Адрес не удалось определить автоматически.');
        }

        $geoObject = $decoded['response']['GeoObjectCollection']['featureMember'][0]['GeoObject'] ?? null;

        if (!is_array($geoObject)) {
            return $this->response(false, 'Адрес не удалось определить автоматически.');
        }

        $metadata = $geoObject['metaDataProperty']['GeocoderMetaData'] ?? [];
        $address = (string) ($metadata['Address']['formatted'] ?? $metadata['text'] ?? $geoObject['name'] ?? '');
        $city = $this->extractCity($metadata['Address']['Components'] ?? []);

        if ($address === '') {
            return $this->response(false, 'Адрес не удалось определить автоматически.');
        }

        return [
            'status' => 200,
            'payload' => [
                'ok' => true,
                'city' => $city,
                'address' => $address,
            ],
        ];
    }

    /**
     * @param mixed $components
     */
    private function extractCity(mixed $components): string
    {
        if (!is_array($components)) {
            return '';
        }

        $fallback = '';

        foreach ($components as $component) {
            if (!is_array($component)) {
                continue;
            }

            $kind = (string) ($component['kind'] ?? '');
            $name = (string) ($component['name'] ?? '');

            if ($kind === 'locality') {
                return $name;
            }

            if ($fallback === '' && in_array($kind, ['province', 'area'], true)) {
                $fallback = $name;
            }
        }

        return $fallback;
    }

    /**
     * @return array{status: int, payload: array<string, mixed>}
     */
    private function response(bool $ok, string $message, int $status = 200): array
    {
        return [
            'status' => $status,
            'payload' => [
                'ok' => $ok,
                'message' => $message,
            ],
        ];
    }
}
