<?php

declare(strict_types=1);

namespace TexHub\MoySklad\Resources;

use TexHub\MoySklad\Config;
use TexHub\MoySklad\Http\HttpClient;
use TexHub\MoySklad\Query;
use TexHub\MoySklad\Responses\ListResponse;

/**
 * Reports — stock (остатки) and assortment.
 *
 * @see https://dev.moysklad.ru/doc/api/remap/1.2/reports/
 */
final class ReportsClient
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly Config $config,
    ) {
    }

    /**
     * Current stock for all products (отчёт «Остатки»).
     *
     * @param Query|array<string, mixed> $query
     */
    public function stockAll(Query|array $query = []): ListResponse
    {
        return ListResponse::from($this->http->get('report/stock/all', $this->q($query)));
    }

    /**
     * Stock grouped by store (по складам).
     *
     * @param Query|array<string, mixed> $query
     */
    public function stockByStore(Query|array $query = []): ListResponse
    {
        return ListResponse::from($this->http->get('report/stock/bystore', $this->q($query)));
    }

    /**
     * Short stock report — quick quantities for the given product hrefs.
     *
     * @param array<int, string> $productHrefs
     *
     * @return array<int, array<string, mixed>>
     */
    public function stockShort(array $productHrefs = []): array
    {
        $query = [];
        if ($productHrefs !== []) {
            $query['filter'] = array_map(static fn (string $href) => 'product=' . $href, $productHrefs);
        }

        return $this->http->get('report/stock/all/current', $query);
    }

    /**
     * Assortment — products, variants, services and bundles with stock.
     *
     * @param Query|array<string, mixed> $query
     */
    public function assortment(Query|array $query = []): ListResponse
    {
        return ListResponse::from($this->http->get('entity/assortment', $this->q($query)));
    }

    /**
     * @param Query|array<string, mixed> $query
     *
     * @return array<string, mixed>
     */
    private function q(Query|array $query): array
    {
        return $query instanceof Query ? $query->toArray() : $query;
    }
}
