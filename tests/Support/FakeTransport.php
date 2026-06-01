<?php

declare(strict_types=1);

namespace TexHub\MoySklad\Tests\Support;

use TexHub\MoySklad\Http\RawResponse;
use TexHub\MoySklad\Http\Transport;

/**
 * In-memory transport for tests.
 */
final class FakeTransport implements Transport
{
    /** @var array<int, array{method: string, url: string, headers: array, json: ?array}> */
    public array $history = [];

    /** @var array<int, RawResponse> */
    private array $queue = [];

    public function __construct(
        private int $defaultStatus = 200,
        private string $defaultBody = '{"rows":[],"meta":{"size":0}}',
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function push(array $payload, int $status = 200): self
    {
        $this->queue[] = new RawResponse($status, (string) json_encode($payload));

        return $this;
    }

    public function request(string $method, string $url, array $headers = [], ?array $json = null): RawResponse
    {
        $this->history[] = compact('method', 'url', 'headers', 'json');

        return $this->queue !== [] ? array_shift($this->queue) : new RawResponse($this->defaultStatus, $this->defaultBody);
    }

    public function last(): array
    {
        return $this->history[count($this->history) - 1];
    }

    public function lastUrl(): string
    {
        return $this->last()['url'];
    }
}
