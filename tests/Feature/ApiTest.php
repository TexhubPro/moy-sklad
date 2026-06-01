<?php

declare(strict_types=1);

namespace TexHub\MoySklad\Tests\Feature;

use PHPUnit\Framework\TestCase;
use TexHub\MoySklad\Config;
use TexHub\MoySklad\Exceptions\ApiException;
use TexHub\MoySklad\MoySklad;
use TexHub\MoySklad\Query;
use TexHub\MoySklad\Tests\Support\FakeTransport;

final class ApiTest extends TestCase
{
    private function ms(FakeTransport $t, ?Config $config = null): MoySklad
    {
        return new MoySklad($config ?? new Config(token: 'TOKEN'), $t);
    }

    public function test_products_list_sends_bearer_and_query(): void
    {
        $t = (new FakeTransport())->push([
            'meta' => ['size' => 1, 'limit' => 50, 'offset' => 0],
            'rows' => [['id' => 'p1', 'name' => 'iPhone']],
        ]);

        $list = $this->ms($t)->products()->list(
            Query::make()->limit(50)->filter('archived', '=', 'false')->expand('images')->search('iphone')
        );

        $this->assertSame('iPhone', $list->rows()[0]['name']);
        $this->assertSame(1, $list->size());

        $req = $t->last();
        $this->assertSame('Bearer TOKEN', $req['headers']['Authorization']);
        $this->assertStringContainsString('api.moysklad.ru/api/remap/1.2/entity/product', $req['url']);
        $this->assertStringContainsString('limit=50', $req['url']);
        $this->assertStringContainsString('filter=archived%3Dfalse', $req['url']);
        $this->assertStringContainsString('search=iphone', $req['url']);
        $this->assertStringContainsString('expand=images', $req['url']);
    }

    public function test_create_product(): void
    {
        $t = (new FakeTransport())->push(['id' => 'new1', 'name' => 'New', 'meta' => ['href' => 'https://x/new1']]);

        $product = $this->ms($t)->products()->create([
            'name' => 'New product',
            'description' => 'Описание',
            'salePrices' => [['value' => 10000, 'priceType' => ['meta' => ['href' => 'https://x/pt']]]],
        ]);

        $this->assertSame('new1', $product->id());
        $this->assertSame('POST', $t->last()['method']);
        $this->assertSame('New product', $t->last()['json']['name']);
        $this->assertSame('Описание', $t->last()['json']['description']);
    }

    public function test_add_product_image_base64(): void
    {
        $t = (new FakeTransport())->push([['id' => 'img1']]);

        $this->ms($t)->products()->addImage('p1', 'photo.jpg', 'BINARY');

        $req = $t->last();
        $this->assertStringContainsString('/entity/product/p1/images', $req['url']);
        $this->assertSame('photo.jpg', $req['json'][0]['filename']);
        $this->assertSame(base64_encode('BINARY'), $req['json'][0]['content']);
    }

    public function test_stock_report(): void
    {
        $t = (new FakeTransport())->push(['rows' => [['stock' => 5]], 'meta' => ['size' => 1]]);

        $stock = $this->ms($t)->reports()->stockAll();

        $this->assertSame(5, $stock->rows()[0]['stock']);
        $this->assertStringContainsString('/report/stock/all', $t->lastUrl());
    }

    public function test_generic_entity_and_delete(): void
    {
        $t = (new FakeTransport())->push([]);

        $this->ms($t)->entity('counterparty')->delete('c1');

        $this->assertSame('DELETE', $t->last()['method']);
        $this->assertStringContainsString('/entity/counterparty/c1', $t->lastUrl());
    }

    public function test_basic_auth_when_login_password(): void
    {
        $t = (new FakeTransport())->push(['rows' => []]);

        $this->ms($t, new Config(login: 'demo', password: 'secret'))->organizations()->list();

        $this->assertSame('Basic ' . base64_encode('demo:secret'), $t->last()['headers']['Authorization']);
    }

    public function test_api_error_is_parsed(): void
    {
        $t = (new FakeTransport())->push([
            'errors' => [['error' => 'Token expired', 'code' => 1056]],
        ], 401);

        try {
            $this->ms($t)->products()->list();
            $this->fail('Expected ApiException');
        } catch (ApiException $e) {
            $this->assertSame(401, $e->httpStatus);
            $this->assertSame(1056, $e->errorCode());
            $this->assertTrue($e->isUnauthorized());
        }
    }

    public function test_multi_tenant_for_token(): void
    {
        $t = (new FakeTransport())->push(['rows' => []])->push(['rows' => []]);
        $base = $this->ms($t);

        $base->forToken('TENANT_A')->products()->list();
        $base->forToken('TENANT_B')->products()->list();

        $this->assertSame('Bearer TENANT_A', $t->history[0]['headers']['Authorization']);
        $this->assertSame('Bearer TENANT_B', $t->history[1]['headers']['Authorization']);
    }
}
