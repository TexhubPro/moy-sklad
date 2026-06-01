<?php

declare(strict_types=1);

namespace TexHub\MoySklad;

use TexHub\MoySklad\Http\CurlTransport;
use TexHub\MoySklad\Http\HttpClient;
use TexHub\MoySklad\Http\Transport;
use TexHub\MoySklad\Resources\EntityClient;
use TexHub\MoySklad\Resources\ProductsClient;
use TexHub\MoySklad\Resources\ReportsClient;
use TexHub\MoySklad\Resources\TokenClient;
use TexHub\MoySklad\Resources\WebhooksClient;
use TexHub\MoySklad\Webhook\WebhookHandler;

/**
 * Entry point of the MoySklad SDK (JSON API 1.2).
 *
 * Framework-agnostic and multi-tenant friendly: build one instance per account
 * with {@see fromArray()} / {@see withToken()}, or resolve from the Laravel
 * container via the {@see \TexHub\MoySklad\Laravel\MoySklad} facade.
 *
 * ```php
 * $ms = MoySklad::withToken('ACCESS_TOKEN');
 * $products = $ms->products()->list(Query::make()->limit(50)->expand('images'));
 * $stock = $ms->reports()->stockAll();
 * ```
 */
final class MoySklad
{
    private readonly Transport $transport;
    private readonly HttpClient $httpClient;

    /** @var array<string, object> */
    private array $resources = [];

    public function __construct(
        private readonly Config $config,
        ?Transport $transport = null,
    ) {
        $this->transport = $transport ?? new CurlTransport($config->timeout);
        $this->httpClient = new HttpClient($config, $this->transport);
    }

    public static function withToken(string $token, ?Transport $transport = null): self
    {
        return new self(new Config(token: $token), $transport);
    }

    public static function withLogin(string $login, string $password, ?Transport $transport = null): self
    {
        return new self(new Config(login: $login, password: $password), $transport);
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config, ?Transport $transport = null): self
    {
        return new self(Config::fromArray($config), $transport);
    }

    public function config(): Config
    {
        return $this->config;
    }

    public function http(): HttpClient
    {
        return $this->httpClient;
    }

    /**
     * Return a new instance authenticated with a different token (per-tenant).
     */
    public function forToken(string $token): self
    {
        return new self($this->config->withToken($token), $this->transport);
    }

    /**
     * Generic CRUD client for any entity type (product, counterparty, demand, …).
     */
    public function entity(string $type): EntityClient
    {
        return new EntityClient($this->httpClient, $this->config, $type);
    }

    public function products(): ProductsClient
    {
        return $this->resources[ProductsClient::class] ??= new ProductsClient($this->httpClient, $this->config);
    }

    public function variants(): EntityClient
    {
        return $this->entity('variant');
    }

    public function services(): EntityClient
    {
        return $this->entity('service');
    }

    public function bundles(): EntityClient
    {
        return $this->entity('bundle');
    }

    public function productFolders(): EntityClient
    {
        return $this->entity('productfolder');
    }

    public function counterparties(): EntityClient
    {
        return $this->entity('counterparty');
    }

    public function organizations(): EntityClient
    {
        return $this->entity('organization');
    }

    public function stores(): EntityClient
    {
        return $this->entity('store');
    }

    public function customerOrders(): EntityClient
    {
        return $this->entity('customerorder');
    }

    public function demands(): EntityClient
    {
        return $this->entity('demand');
    }

    public function reports(): ReportsClient
    {
        return $this->resources[ReportsClient::class] ??= new ReportsClient($this->httpClient, $this->config);
    }

    public function webhooks(): WebhooksClient
    {
        return $this->resources[WebhooksClient::class] ??= new WebhooksClient($this->httpClient, $this->config);
    }

    public function webhookHandler(): WebhookHandler
    {
        return $this->resources[WebhookHandler::class] ??= new WebhookHandler();
    }

    /**
     * Exchange the configured login + password for a new access token.
     */
    public function tokens(): TokenClient
    {
        return $this->resources[TokenClient::class] ??= new TokenClient($this->httpClient, $this->config);
    }
}
