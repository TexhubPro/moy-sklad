<?php

declare(strict_types=1);

namespace TexHub\MoySklad;

use TexHub\MoySklad\Exceptions\ConfigurationException;

/**
 * Immutable SDK configuration for the MoySklad JSON API 1.2.
 *
 * Authenticate with either a bearer token (recommended) or login + password
 * (HTTP Basic). For multi-tenant use, build one config/client per account with
 * {@see withToken()} or {@see fromArray()}.
 */
final class Config
{
    public const DEFAULT_BASE_URL = 'https://api.moysklad.ru/api/remap/1.2';

    /**
     * @param string|null $token    Bearer access token.
     * @param string|null $login    Account login (for HTTP Basic / token exchange).
     * @param string|null $password Account password.
     * @param string      $baseUrl  API base URL (no trailing slash).
     * @param int         $timeout  HTTP timeout in seconds.
     */
    public function __construct(
        public readonly ?string $token = null,
        public readonly ?string $login = null,
        public readonly ?string $password = null,
        public readonly string $baseUrl = self::DEFAULT_BASE_URL,
        public readonly int $timeout = 30,
    ) {
        if ($this->token === null && ($this->login === null || $this->password === null)) {
            throw new ConfigurationException('MoySklad requires either a token or a login + password.');
        }

        if ($this->timeout < 1) {
            throw new ConfigurationException('MoySklad timeout must be a positive number of seconds.');
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config): self
    {
        return new self(
            token: self::nullableString($config['token'] ?? null),
            login: self::nullableString($config['login'] ?? null),
            password: self::nullableString($config['password'] ?? null),
            baseUrl: (string) ($config['base_url'] ?? self::DEFAULT_BASE_URL),
            timeout: (int) ($config['timeout'] ?? 30),
        );
    }

    public function baseUrl(): string
    {
        return rtrim($this->baseUrl, '/');
    }

    public function url(string $path): string
    {
        return $this->baseUrl() . '/' . ltrim($path, '/');
    }

    /**
     * The Authorization header value (Bearer token or Basic credentials).
     */
    public function authorizationHeader(): string
    {
        if ($this->token !== null) {
            return 'Bearer ' . $this->token;
        }

        return 'Basic ' . base64_encode($this->login . ':' . $this->password);
    }

    /**
     * Return a copy authenticated with a different token (per-tenant use).
     */
    public function withToken(string $token): self
    {
        return new self(token: $token, baseUrl: $this->baseUrl, timeout: $this->timeout);
    }

    private static function nullableString(mixed $value): ?string
    {
        return $value === null || $value === '' ? null : (string) $value;
    }
}
