<?php

declare(strict_types=1);

namespace TexHub\MoySklad\Resources;

use TexHub\MoySklad\Config;
use TexHub\MoySklad\Http\HttpClient;
use TexHub\MoySklad\Responses\Response;

/**
 * Manage webhook subscriptions (`/entity/webhook`).
 *
 * @see https://dev.moysklad.ru/doc/api/remap/1.2/#mojsklad-json-api-vebhuki
 */
final class WebhooksClient extends EntityClient
{
    public function __construct(HttpClient $http, Config $config)
    {
        parent::__construct($http, $config, 'webhook');
    }

    /**
     * Subscribe to changes of an entity type.
     *
     * @param string $entityType e.g. "product", "customerorder", "demand".
     * @param string $action     CREATE | UPDATE | DELETE.
     * @param array<string, mixed> $extra Additional fields (diffType, method, …).
     */
    public function subscribe(string $url, string $entityType, string $action = 'UPDATE', array $extra = []): Response
    {
        return $this->create([
            'url' => $url,
            'action' => $action,
            'entityType' => $entityType,
            'enabled' => true,
        ] + $extra);
    }

    /**
     * Enable or disable a webhook.
     */
    public function setEnabled(string $id, bool $enabled): Response
    {
        return $this->update($id, ['enabled' => $enabled]);
    }
}
