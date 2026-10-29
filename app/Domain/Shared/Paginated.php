<?php

namespace App\Domain\Shared;

/**
 * A page of results returned by a repository.
 *
 * @template T
 */
final readonly class Paginated
{
    /**
     * @param  list<T>  $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
    ) {}
}
