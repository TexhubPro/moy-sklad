<?php

declare(strict_types=1);

namespace TexHub\MoySklad\Resources;

use TexHub\MoySklad\Config;
use TexHub\MoySklad\Http\HttpClient;

/**
 * Exchange login + password for a bearer access token (`POST /security/token`).
 *
 * Useful in multi-tenant onboarding: a customer provides login/password once,
 * you store the returned token and use it for all further requests.
 */
final class TokenClient
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly Config $config,
    ) {
    }

    /**
     * Request a new access token for the configured login + password.
     */
    public function create(): string
    {
        $response = $this->http->post('security/token');

        return (string) ($response['access_token'] ?? '');
    }
}
