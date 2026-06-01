<?php

declare(strict_types=1);

namespace TexHub\MoySklad\Resources;

use TexHub\MoySklad\Config;
use TexHub\MoySklad\Http\HttpClient;
use TexHub\MoySklad\Query;
use TexHub\MoySklad\Responses\ListResponse;
use TexHub\MoySklad\Responses\Response;

/**
 * Generic CRUD client for any MoySklad entity (`/entity/{type}`).
 *
 * Used directly via {@see \TexHub\MoySklad\MoySklad::entity()} or as the base
 * for typed resource clients (products, counterparty, …).
 *
 * @see https://dev.moysklad.ru/doc/api/remap/1.2/
 */
class EntityClient
{
    public function __construct(
        protected readonly HttpClient $http,
        protected readonly Config $config,
        protected readonly string $entity,
    ) {
    }

    /**
     * List entities.
     *
     * @param Query|array<string, mixed> $query
     */
    public function list(Query|array $query = []): ListResponse
    {
        return ListResponse::from($this->http->get($this->path(), $this->query($query)));
    }

    /**
     * Get one entity by id.
     *
     * @param Query|array<string, mixed> $query e.g. ['expand' => 'images']
     */
    public function get(string $id, Query|array $query = []): Response
    {
        return Response::from($this->http->get($this->path($id), $this->query($query)));
    }

    /**
     * Create an entity.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): Response
    {
        return Response::from($this->http->post($this->path(), $data));
    }

    /**
     * Update an entity by id.
     *
     * @param array<string, mixed> $data
     */
    public function update(string $id, array $data): Response
    {
        return Response::from($this->http->put($this->path($id), $data));
    }

    /**
     * Create or update many entities in one request (mass operation).
     *
     * @param array<int, array<string, mixed>> $rows
     */
    public function massUpsert(array $rows): ListResponse
    {
        return ListResponse::from(['rows' => $this->http->post($this->path(), $rows)]);
    }

    public function delete(string $id): void
    {
        $this->http->delete($this->path($id));
    }

    /**
     * Get the entity metadata (fields, attributes, states).
     */
    public function metadata(): Response
    {
        return Response::from($this->http->get($this->path() . '/metadata'));
    }

    protected function path(?string $id = null): string
    {
        $path = 'entity/' . $this->entity;

        return $id === null ? $path : $path . '/' . rawurlencode($id);
    }

    /**
     * @param Query|array<string, mixed> $query
     *
     * @return array<string, mixed>
     */
    protected function query(Query|array $query): array
    {
        return $query instanceof Query ? $query->toArray() : $query;
    }
}
