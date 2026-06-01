<?php

declare(strict_types=1);

namespace TexHub\MoySklad\Responses;

/**
 * A MoySklad list response (`{ context, meta: {size,limit,offset,nextHref}, rows: [...] }`).
 */
final class ListResponse extends Response
{
    /**
     * The list items.
     *
     * @return array<int, array<string, mixed>>
     */
    public function rows(): array
    {
        $rows = $this->get('rows', []);

        return is_array($rows) ? $rows : [];
    }

    /**
     * Wrap each row in a {@see Response} for uniform access.
     *
     * @return array<int, Response>
     */
    public function items(): array
    {
        return array_map(static fn (array $row) => new Response($row), $this->rows());
    }

    public function size(): int
    {
        return (int) $this->get('meta.size', count($this->rows()));
    }

    public function limit(): int
    {
        return (int) $this->get('meta.limit', 0);
    }

    public function offset(): int
    {
        return (int) $this->get('meta.offset', 0);
    }

    public function nextHref(): ?string
    {
        $next = $this->get('meta.nextHref');

        return $next === null ? null : (string) $next;
    }

    public function hasMore(): bool
    {
        return $this->nextHref() !== null;
    }
}
