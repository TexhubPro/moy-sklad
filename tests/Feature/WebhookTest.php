<?php

declare(strict_types=1);

namespace TexHub\MoySklad\Tests\Feature;

use PHPUnit\Framework\TestCase;
use TexHub\MoySklad\Webhook\WebhookHandler;

final class WebhookTest extends TestCase
{
    public function test_parses_events_with_account_id_for_routing(): void
    {
        $handler = new WebhookHandler();

        $body = json_encode([
            'auditContext' => ['meta' => []],
            'events' => [
                [
                    'meta' => ['type' => 'product', 'href' => 'https://api.moysklad.ru/api/remap/1.2/entity/product/abc-123'],
                    'action' => 'UPDATE',
                    'accountId' => 'TENANT-1',
                    'updatedFields' => ['name', 'salePrices'],
                ],
                [
                    'meta' => ['type' => 'customerorder', 'href' => 'https://api.moysklad.ru/api/remap/1.2/entity/customerorder/ord-9'],
                    'action' => 'CREATE',
                    'accountId' => 'TENANT-1',
                ],
            ],
        ]);

        $events = $handler->parse($body);
        $this->assertCount(2, $events);

        $first = $events[0];
        $this->assertTrue($first->isUpdate());
        $this->assertSame('product', $first->entityType);
        $this->assertSame('abc-123', $first->entityId());
        $this->assertSame('TENANT-1', $first->accountId);
        $this->assertSame(['name', 'salePrices'], $first->updatedFields);

        $this->assertTrue($events[1]->isCreate());
        $this->assertSame('ord-9', $events[1]->entityId());

        // Multi-tenant routing key:
        $this->assertSame('TENANT-1', $handler->accountId($body));
    }
}
