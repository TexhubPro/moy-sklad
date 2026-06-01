# TexHub · MoySklad

**🌐 English** · [Русский](README.ru.md)

[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/php-%5E8.2-777bb4.svg)](composer.json)
[![Laravel](https://img.shields.io/badge/laravel-11%20%7C%2012%20%7C%2013-ff2d20.svg)](#laravel)

A full-featured, framework-agnostic PHP SDK for the **MoySklad (МойСклад) JSON API 1.2** — products, stock, images, counterparties, orders, reports and webhooks — with first-class **Laravel** and **multi-tenant** support.

Reference: <https://dev.moysklad.ru/doc/api/remap/1.2/>

---

## ✨ Features

- 📦 **Products** — full CRUD with name, code, article, description, prices, attributes
- 🖼 **Images** — list, upload (file/base64), delete
- 📊 **Stock** — `report/stock/all`, by store, short stock, assortment
- 🧩 **Generic entity client** — reach *any* entity (`counterparty`, `customerorder`, `demand`, …)
- 🔔 **Webhooks** — manage subscriptions + parse incoming events (with `accountId` for tenant routing)
- 🏢 **Multi-tenant** — one client per account via token; `forToken()` switching
- 🔎 **Query builder** — pagination, filters, search, order, `expand`
- 🔑 Token or login/password auth · gzip · typed responses · fully tested

---

## 📦 Installation

```bash
composer require texhub/moy-sklad
```

Requirements: **PHP ≥ 8.2** with `curl`, `json`.

---

## 🚀 Quick start

```php
use TexHub\MoySklad\MoySklad;
use TexHub\MoySklad\Query;

$ms = MoySklad::withToken('YOUR_ACCESS_TOKEN');
// or: MoySklad::withLogin('login', 'password');

// List products with stock-friendly query:
$products = $ms->products()->list(
    Query::make()->limit(50)->filter('archived', '=', 'false')->expand('images')->search('iphone')
);

foreach ($products->rows() as $product) {
    echo $product['name'] . ' — ' . ($product['code'] ?? '') . PHP_EOL;
}
```

### Get a token from login/password

```php
$token = MoySklad::withLogin('login', 'password')->tokens()->create();
// store $token and reuse it
```

---

## 📦 Products, descriptions & images

```php
// Create with description and a sale price:
$product = $ms->products()->create([
    'name' => 'Смартфон X',
    'code' => 'SKU-001',
    'article' => 'ART-001',
    'description' => 'Полное описание товара…',
    'salePrices' => [['value' => 999000, 'priceType' => ['meta' => ['href' => $priceTypeHref, 'type' => 'pricetype', 'mediaType' => 'application/json']]]],
]);

// Update:
$ms->products()->update($product->id(), ['description' => 'Обновлённое описание']);

// Images:
$ms->products()->images($product->id());                       // list
$ms->products()->addImageFromFile($product->id(), '/path/photo.jpg');
$ms->products()->addImage($product->id(), 'photo.jpg', $binary); // base64 handled for you
$ms->products()->deleteImage($product->id(), $imageId);
```

---

## 📊 Stock (количество) & assortment

```php
$ms->reports()->stockAll();                 // отчёт «Остатки» по всем товарам
$ms->reports()->stockByStore();             // остатки по складам
$ms->reports()->assortment(Query::make()->limit(100)); // товары+модификации+услуги со складскими данными
```

Each stock row contains `stock`, `reserve`, `inTransit`, `quantity`, plus the linked product `meta`.

---

## 🧩 Any entity

```php
$ms->counterparties()->list();
$ms->customerOrders()->get($orderId, Query::make()->expand('positions', 'agent'));
$ms->entity('demand')->create([...]);       // any MoySklad entity type
$ms->entity('store')->metadata();
```

Built-in helpers: `products()`, `variants()`, `services()`, `bundles()`, `productFolders()`, `counterparties()`, `organizations()`, `stores()`, `customerOrders()`, `demands()`, `reports()`, `webhooks()`.

---

## 🔔 Webhooks

**Subscribe** (so MoySklad notifies your URL):

```php
$ms->webhooks()->subscribe('https://shop.tj/moysklad/webhook', 'product', 'UPDATE');
$ms->webhooks()->subscribe('https://shop.tj/moysklad/webhook', 'customerorder', 'CREATE');
```

**Receive** (parse the incoming POST):

```php
foreach ($ms->webhookHandler()->parse(file_get_contents('php://input')) as $event) {
    $event->action;         // CREATE | UPDATE | DELETE
    $event->entityType;     // product, customerorder, …
    $event->entityId();     // parsed from href
    $event->updatedFields;  // changed fields (UPDATE)
    $event->accountId;      // → which tenant this belongs to
}
http_response_code(200);
```

---

## 🏢 Multi-tenant / SaaS

Each customer connects their own MoySklad account. Store their token (or login/password → token) and build a per-tenant client:

```php
// Onboard once:
$token = MoySklad::withLogin($tenant->ms_login, $tenant->ms_password)->tokens()->create();

// Act as any tenant:
$ms->forToken($tenant->ms_token)->products()->list();

// Route incoming webhooks by account:
$accountId = $ms->webhookHandler()->accountId($raw);
$tenant = Tenant::where('ms_account_id', $accountId)->first();
```

---

## ⚙️ Error handling

```php
use TexHub\MoySklad\Exceptions\ApiException;

try {
    $ms->products()->create($data);
} catch (ApiException $e) {
    $e->httpStatus;
    $e->errorCode();   // MoySklad error code
    $e->errors;        // [['error' => ..., 'code' => ..., 'parameter' => ...]]
    $e->isUnauthorized();
}
```

---

## <a name="laravel"></a>🧩 Laravel

Auto-discovered. Publish config:

```bash
php artisan vendor:publish --tag=moy-sklad-config
```

`.env`:

```dotenv
MOYSKLAD_TOKEN=your_token
# or
MOYSKLAD_LOGIN=login
MOYSKLAD_PASSWORD=password
```

Facade:

```php
use TexHub\MoySklad\Laravel\MoySklad;

MoySklad::products()->list();
MoySklad::forToken($tenant->ms_token)->reports()->stockAll();   // multi-tenant
```

> Exclude your webhook route from CSRF (`VerifyCsrfToken::$except`).

---

## 🧪 Testing

```php
use TexHub\MoySklad\MoySklad;
use TexHub\MoySklad\Config;
use TexHub\MoySklad\Tests\Support\FakeTransport;

$t = (new FakeTransport())->push(['rows' => [['id' => 'p1']], 'meta' => ['size' => 1]]);
$ms = new MoySklad(new Config(token: 'TOKEN'), $t);
$ms->products()->list();  // assert on $t->last()
```

```bash
composer install && composer test
```

---

## 📚 Architecture

```
src/
├── MoySklad.php             # entry — products()/reports()/webhooks()/entity()/forToken()
├── Config.php               # token or login/password auth, multi-tenant withToken()
├── Query.php                # filters / search / order / expand / pagination
├── Http/                    # Transport, CurlTransport (gzip), HttpClient, RawResponse
├── Resources/               # EntityClient (generic CRUD), Products, Reports, Webhooks, Token
├── Webhook/                 # WebhookHandler + WebhookEvent (accountId routing)
├── Responses/               # Response (ArrayAccess), ListResponse (rows + meta)
├── Exceptions/              # ApiException, TransportException, …
└── Laravel/                 # ServiceProvider + Facade
```

---

## License

MIT © TexHub Pro — built by Mahmudi Shodmehr.
