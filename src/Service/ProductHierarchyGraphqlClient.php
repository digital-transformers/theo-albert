<?php
declare(strict_types=1);

namespace App\Service;

use JsonException;
use RuntimeException;

class ProductHierarchyGraphqlClient
{
    private const MAX_ATTEMPTS = 4;

    public function __construct(
        private readonly string $endpointUrl,
        private readonly string $apiKey,
    ) {
    }

    /**
     * @param array<string, mixed> $variables
     *
     * @return array<string, mixed>
     */
    public function execute(string $query, array $variables = []): array
    {
        $url = $this->endpointUrl . (str_contains($this->endpointUrl, '?') ? '&' : '?')
            . 'apikey=' . rawurlencode($this->apiKey);
        $body = json_encode(
            ['query' => $query, 'variables' => $variables],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; ++$attempt) {
            [$response, $statusCode, $error] = $this->request($url, $body);
            $retryable = !is_string($response) || $statusCode === 429 || $statusCode >= 500;
            if (!$retryable || $attempt === self::MAX_ATTEMPTS) {
                break;
            }

            usleep(250_000 * (2 ** ($attempt - 1)));
        }

        if (!is_string($response)) {
            throw new RuntimeException('ProductHierarchy request failed: ' . ($error ?: 'unknown cURL error'));
        }
        if ($statusCode < 200 || $statusCode >= 300) {
            throw new RuntimeException(sprintf(
                'ProductHierarchy returned HTTP %d: %s',
                $statusCode,
                mb_substr(strip_tags($response), 0, 500)
            ));
        }

        try {
            $decoded = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('ProductHierarchy returned invalid JSON.', 0, $exception);
        }
        if (!is_array($decoded)) {
            throw new RuntimeException('ProductHierarchy returned an invalid response.');
        }
        if (isset($decoded['errors']) && is_array($decoded['errors']) && $decoded['errors'] !== []) {
            $messages = array_map(
                static fn (mixed $error): string => is_array($error)
                    ? (string) ($error['message'] ?? 'Unknown GraphQL error')
                    : 'Unknown GraphQL error',
                $decoded['errors']
            );

            throw new RuntimeException('ProductHierarchy GraphQL error: ' . implode('; ', $messages));
        }

        return is_array($decoded['data'] ?? null) ? $decoded['data'] : [];
    }

    /**
     * @return array{0: string|false, 1: int, 2: string}
     */
    private function request(string $url, string $body): array
    {
        $curl = curl_init($url);
        if ($curl === false) {
            throw new RuntimeException('Unable to initialize GraphQL HTTP client.');
        }

        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 120,
        ]);

        $response = curl_exec($curl);

        return [
            $response,
            (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE),
            curl_error($curl),
        ];
    }
}
