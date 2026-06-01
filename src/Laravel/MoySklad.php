<?php

declare(strict_types=1);

namespace TexHub\MoySklad\Laravel;

use Illuminate\Support\Facades\Facade;

/**
 * Laravel facade for the MoySklad client.
 *
 * @method static \TexHub\MoySklad\Resources\ProductsClient  products()
 * @method static \TexHub\MoySklad\Resources\EntityClient    entity(string $type)
 * @method static \TexHub\MoySklad\Resources\EntityClient    counterparties()
 * @method static \TexHub\MoySklad\Resources\EntityClient    organizations()
 * @method static \TexHub\MoySklad\Resources\EntityClient    stores()
 * @method static \TexHub\MoySklad\Resources\EntityClient    productFolders()
 * @method static \TexHub\MoySklad\Resources\EntityClient    customerOrders()
 * @method static \TexHub\MoySklad\Resources\ReportsClient   reports()
 * @method static \TexHub\MoySklad\Resources\WebhooksClient  webhooks()
 * @method static \TexHub\MoySklad\Webhook\WebhookHandler     webhookHandler()
 * @method static \TexHub\MoySklad\MoySklad                  forToken(string $token)
 * @method static \TexHub\MoySklad\Config                    config()
 *
 * @see \TexHub\MoySklad\MoySklad
 */
class MoySklad extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'moy-sklad';
    }
}
