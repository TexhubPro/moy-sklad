<?php

declare(strict_types=1);

namespace TexHub\MoySklad;

/**
 * Fluent builder for MoySklad list query parameters: pagination, filters,
 * full-text search, ordering and `expand`.
 *
 * ```php
 * Query::make()
 *     ->limit(50)->offset(0)
 *     ->filter('archived', '=', 'false')
 *     ->search('iphone')
 *     ->order('name', 'asc')
 *     ->expand('images', 'supplier')
 *     ->toArray();
 * ```
 */
final class Query
{
    private ?int $limit = null;
    private ?int $offset = null;
    private ?string $search = null;

    /** @var array<int, string> */
    private array $filters = [];

    /** @var array<int, string> */
    private array $order = [];

    /** @var array<int, string> */
    private array $expand = [];

    public static function make(): self
    {
        return new self();
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;

        return $this;
    }

    public function search(string $term): self
    {
        $this->search = $term;

        return $this;
    }

    /**
     * Add a filter, e.g. filter('name', '=', 'Acme') or filter('sum', '>', 100).
     * Operators: =, !=, >, <, >=, <=, ~ (contains), ~= (starts), =~ (ends).
     */
    public function filter(string $field, string $operator, string $value): self
    {
        $this->filters[] = $field . $operator . $value;

        return $this;
    }

    public function whereUrl(string $field, string $href): self
    {
        $this->filters[] = $field . '=' . $href;

        return $this;
    }

    public function order(string $field, string $direction = 'asc'): self
    {
        $this->order[] = $field . ',' . $direction;

        return $this;
    }

    public function expand(string ...$fields): self
    {
        foreach ($fields as $field) {
            $this->expand[] = $field;
        }

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $query = [];

        if ($this->limit !== null) {
            $query['limit'] = $this->limit;
        }
        if ($this->offset !== null) {
            $query['offset'] = $this->offset;
        }
        if ($this->search !== null) {
            $query['search'] = $this->search;
        }
        if ($this->filters !== []) {
            $query['filter'] = $this->filters;
        }
        if ($this->order !== []) {
            $query['order'] = implode(';', $this->order);
        }
        if ($this->expand !== []) {
            $query['expand'] = implode(',', $this->expand);
            $query['limit'] ??= 100;
        }

        return $query;
    }
}
