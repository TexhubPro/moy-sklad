<?php

declare(strict_types=1);

namespace TexHub\MoySklad\Http;

use TexHub\MoySklad\Config;
use TexHub\MoySklad\Exceptions\ApiException;
use TexHub\MoySklad\Exceptions\MoySkladException;

/**
 * HTTP wrapper: applies auth + JSON headers, decodes responses and converts
 * MoySklad error payloads into {@see ApiException}.
 */
final class HttpClient
{
    public function __construct(
        private readonly Config $config,
        private readonly Transport $transport,
    ) {
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return array<string, mixed>
     */
    public function get(string $path, array $query = []): array
    {
        $url = $this->config->url($path);
        if ($query !== []) {
            $url .= '?' . $this->buildQuery($query);
        }

        return $this->decode($this->transport->request('GET', $url, $this->headers()));
    }

    /**
     * @param array<mixed> $body
     *
     * @return array<string, mixed>
     */
    public function post(string $path, array $body = []): array
    {
        return $this->decode($this->transport->request('POST', $this->config->url($path), $this->headers(), json: $body));
    }

    /**
     * @param array<mixed> $body
     *
     * @return array<string, mixed>
     */
    public function put(string $path, array $body = []): array
    {
        return $this->decode($this->transport->request('PUT', $this->config->url($path), $this->headers(), json: $body));
    }

    /**
     * @return array<string, mixed>
     */
    public function delete(string $path): array
    {
        return $this->decode($this->transport->request('DELETE', $this->config->url($path), $this->headers()));
    }

    /**
     * GET returning the raw response (for binary downloads such as image files).
     * Accepts an absolute URL or a path. Throws on HTTP error.
     */
    public function getRaw(string $urlOrPath): RawResponse
    {
        $url = str_starts_with($urlOrPath, 'http') ? $urlOrPath : $this->config->url($urlOrPath);

        $response = $this->transport->request('GET', $url, $this->headers());

        if (! $response->isSuccessful()) {
            $this->decode($response);
        }

        return $response;
    }

    /**
     * Build a MoySklad query string. Array values for `filter` are joined with
     * `;` (e.g. ['filter' => ['archived=false', 'name=Acme']]).
     *
     * @param array<string, mixed> $query
     */
    private function buildQuery(array $query): string
    {
        $parts = [];
        foreach ($query as $key => $value) {
            if ($value === null) {
                continue;
            }
            if (is_array($value)) {
                $value = implode(';', $value);
            }
            $parts[] = rawurlencode((string) $key) . '=' . rawurlencode((string) $value);
        }

        return implode('&', $parts);
    }

    /**
     * @return array<string, string>
     */
    private function headers(): array
    {
        return [
            'Authorization' => $this->config->authorizationHeader(),
            'Accept' => 'application/json;charset=utf-8',
            'Accept-Encoding' => 'gzip',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(RawResponse $response): array
    {
        if ($response->statusCode === 204 || $response->body === '') {
            if ($response->isSuccessful()) {
                return [];
            }
            throw new ApiException('MoySklad API error (HTTP ' . $response->statusCode . ')', $response->statusCode);
        }

        $decoded = json_decode($response->body, true);

        if (! is_array($decoded)) {
            if ($response->isSuccessful()) {
                throw new MoySkladException('Unexpected non-JSON response from MoySklad: ' . substr($response->body, 0, 200));
            }
            throw new ApiException('MoySklad API error (HTTP ' . $response->statusCode . ')', $response->statusCode);
        }

        if (! $response->isSuccessful() || isset($decoded['errors'])) {
            throw ApiException::fromResponse($response->statusCode, $decoded);
        }

        return $decoded;
    }
}
