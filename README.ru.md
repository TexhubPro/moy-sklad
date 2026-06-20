# TexHub · MoySklad

[English](README.md) · **Русский**

[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/php-%5E8.2-777bb4.svg)](composer.json)
[![Laravel](https://img.shields.io/badge/laravel-11%20%7C%2012%20%7C%2013-ff2d20.svg)](#laravel)

Полнофункциональный, не привязанный к фреймворку PHP SDK для **МойСклад JSON API 1.2** — товары, остатки, фото, контрагенты, заказы, отчёты и вебхуки — с полной поддержкой **Laravel** и **multi-tenant**.

Документация: <https://dev.moysklad.ru/doc/api/remap/1.2/>

---

## Возможности

- **Товары** — полный CRUD: имя, код, артикул, описание, цены, атрибуты
- **Фото** — список, загрузка (файл/base64), удаление
- **Остатки** — `report/stock/all`, по складам, краткие остатки, ассортимент
- **Generic-клиент** — доступ к *любой* сущности (`counterparty`, `customerorder`, `demand`, …)
- **Вебхуки** — управление подписками + разбор входящих событий (с `accountId` для роутинга по арендатору)
- **Multi-tenant** — отдельный клиент на аккаунт через токен; переключение `forToken()`
- **Query-билдер** — пагинация, фильтры, поиск, сортировка, `expand`
- Авторизация токеном или логин/пароль · gzip · типизированные ответы · полностью покрыт тестами

---

## Установка

```bash
composer require texhub/moy-sklad
```

Требования: **PHP ≥ 8.2** с `curl`, `json`.

---

## Быстрый старт

```php
use TexHub\MoySklad\MoySklad;
use TexHub\MoySklad\Query;

$ms = MoySklad::withToken('YOUR_ACCESS_TOKEN');
// или: MoySklad::withLogin('login', 'password');

$products = $ms->products()->list(
    Query::make()->limit(50)->filter('archived', '=', 'false')->expand('images')->search('iphone')
);

foreach ($products->rows() as $product) {
    echo $product['name'] . ' — ' . ($product['code'] ?? '') . PHP_EOL;
}
```

### Получить токен по логину/паролю

```php
$token = MoySklad::withLogin('login', 'password')->tokens()->create();
// сохраните $token и переиспользуйте
```

---

## Товары, описания и фото

```php
// Создание с описанием и ценой:
$product = $ms->products()->create([
    'name' => 'Смартфон X',
    'code' => 'SKU-001',
    'article' => 'ART-001',
    'description' => 'Полное описание товара…',
    'salePrices' => [['value' => 999000, 'priceType' => ['meta' => ['href' => $priceTypeHref, 'type' => 'pricetype', 'mediaType' => 'application/json']]]],
]);

// Обновление:
$ms->products()->update($product->id(), ['description' => 'Обновлённое описание']);

// Фото:
$ms->products()->images($product->id());                       // список
$ms->products()->addImageFromFile($product->id(), '/path/photo.jpg');
$ms->products()->addImage($product->id(), 'photo.jpg', $binary); // base64 сделаем за вас
$ms->products()->deleteImage($product->id(), $imageId);
```

---

## Остатки (количество) и ассортимент

```php
$ms->reports()->stockAll();                 // отчёт «Остатки» по всем товарам
$ms->reports()->stockByStore();             // остатки по складам
$ms->reports()->assortment(Query::make()->limit(100)); // товары+модификации+услуги со складскими данными
```

Каждая строка остатков содержит `stock`, `reserve`, `inTransit`, `quantity` и `meta` связанного товара.

---

## Любая сущность

```php
$ms->counterparties()->list();
$ms->customerOrders()->get($orderId, Query::make()->expand('positions', 'agent'));
$ms->entity('demand')->create([...]);       // любой тип сущности МойСклад
$ms->entity('store')->metadata();
```

Встроенные хелперы: `products()`, `variants()`, `services()`, `bundles()`, `productFolders()`, `counterparties()`, `organizations()`, `stores()`, `customerOrders()`, `demands()`, `reports()`, `webhooks()`.

---

## Вебхуки

**Подписка** (чтобы МойСклад уведомлял ваш URL):

```php
$ms->webhooks()->subscribe('https://shop.tj/moysklad/webhook', 'product', 'UPDATE');
$ms->webhooks()->subscribe('https://shop.tj/moysklad/webhook', 'customerorder', 'CREATE');
```

**Приём** (разбор входящего POST):

```php
foreach ($ms->webhookHandler()->parse(file_get_contents('php://input')) as $event) {
    $event->action;         // CREATE | UPDATE | DELETE
    $event->entityType;     // product, customerorder, …
    $event->entityId();     // извлекается из href
    $event->updatedFields;  // изменённые поля (UPDATE)
    $event->accountId;      // → какому арендатору принадлежит
}
http_response_code(200);
```

---

## Multi-tenant / SaaS

Каждый клиент подключает свой аккаунт МойСклад. Храните его токен (или логин/пароль → токен) и стройте клиент на арендатора:

```php
// Онбординг один раз:
$token = MoySklad::withLogin($tenant->ms_login, $tenant->ms_password)->tokens()->create();

// Действуем от любого арендатора:
$ms->forToken($tenant->ms_token)->products()->list();

// Роутинг входящих вебхуков по аккаунту:
$accountId = $ms->webhookHandler()->accountId($raw);
$tenant = Tenant::where('ms_account_id', $accountId)->first();
```

---

## Обработка ошибок

```php
use TexHub\MoySklad\Exceptions\ApiException;

try {
    $ms->products()->create($data);
} catch (ApiException $e) {
    $e->httpStatus;
    $e->errorCode();   // код ошибки МойСклад
    $e->errors;        // [['error' => ..., 'code' => ..., 'parameter' => ...]]
    $e->isUnauthorized();
}
```

---

## <a name="laravel"></a> Laravel

Регистрируется автоматически. Опубликуйте конфиг:

```bash
php artisan vendor:publish --tag=moy-sklad-config
```

`.env`:

```dotenv
MOYSKLAD_TOKEN=your_token
# или
MOYSKLAD_LOGIN=login
MOYSKLAD_PASSWORD=password
```

Фасад:

```php
use TexHub\MoySklad\Laravel\MoySklad;

MoySklad::products()->list();
MoySklad::forToken($tenant->ms_token)->reports()->stockAll();   // multi-tenant
```

> Исключите маршрут вебхука из CSRF (`VerifyCsrfToken::$except`).

---

## Тестирование

```php
use TexHub\MoySklad\MoySklad;
use TexHub\MoySklad\Config;
use TexHub\MoySklad\Tests\Support\FakeTransport;

$t = (new FakeTransport())->push(['rows' => [['id' => 'p1']], 'meta' => ['size' => 1]]);
$ms = new MoySklad(new Config(token: 'TOKEN'), $t);
$ms->products()->list();  // проверяйте $t->last()
```

```bash
composer install && composer test
```

---

## Архитектура

```
src/
├── MoySklad.php             # точка входа — products()/reports()/webhooks()/entity()/forToken()
├── Config.php               # авторизация токеном/логином, multi-tenant withToken()
├── Query.php                # фильтры / поиск / сортировка / expand / пагинация
├── Http/                    # Transport, CurlTransport (gzip), HttpClient, RawResponse
├── Resources/               # EntityClient (generic CRUD), Products, Reports, Webhooks, Token
├── Webhook/                 # WebhookHandler + WebhookEvent (роутинг по accountId)
├── Responses/               # Response (ArrayAccess), ListResponse (rows + meta)
├── Exceptions/              # ApiException, TransportException, …
└── Laravel/                 # ServiceProvider + Facade
```

---

## Лицензия

MIT © TexHub Pro — разработано Mahmudi Shodmehr.
