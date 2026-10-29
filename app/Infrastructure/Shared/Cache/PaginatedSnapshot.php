<?php

namespace App\Infrastructure\Shared\Cache;

use App\Domain\Shared\Paginated;
use Closure;

/**
 * Converts pages to plain arrays and back. The cache only stores primitives:
 * unserializing objects is disabled (cache.serializable_classes) because it
 * can be abused to run code if someone writes to the cache store.
 */
final class PaginatedSnapshot
{
    /**
     * @param  Closure(mixed): array<string, mixed>  $item
     * @return array{items: list<array<string, mixed>>, total: int, page: int, perPage: int}
     */
    public static function toArray(Paginated $page, Closure $item): array
    {
        return [
            'items' => array_map($item, $page->items),
            'total' => $page->total,
            'page' => $page->page,
            'perPage' => $page->perPage,
        ];
    }

    /**
     * @param  array{items: list<array<string, mixed>>, total: int, page: int, perPage: int}  $data
     * @param  Closure(array<string, mixed>): mixed  $item
     */
    public static function fromArray(array $data, Closure $item): Paginated
    {
        return new Paginated(array_map($item, $data['items']), $data['total'], $data['page'], $data['perPage']);
    }
}
