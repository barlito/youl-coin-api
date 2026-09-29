<?php

declare(strict_types=1);

namespace App\State\Pagination;

use ApiPlatform\State\Pagination\PaginatorInterface;
use App\ApiResource\WalletTransactionView;

/**
 * @implements PaginatorInterface<WalletTransactionView>
 * @implements \IteratorAggregate<mixed, WalletTransactionView>
 */
final readonly class WalletTransactionPaginator implements \IteratorAggregate, \Countable, PaginatorInterface
{
    /**
     * @param WalletTransactionView[] $items already the current page's slice, fetched with LIMIT/OFFSET
     */
    public function __construct(
        private array $items,
        private int $page,
        private int $itemsPerPage,
        private int $totalItems,
    ) {
    }

    public function getCurrentPage(): float
    {
        return (float) $this->page;
    }

    public function getLastPage(): float
    {
        if ($this->itemsPerPage <= 0) {
            return 1.0;
        }

        return (float) max(1, (int) ceil($this->totalItems / $this->itemsPerPage));
    }

    public function getItemsPerPage(): float
    {
        return (float) $this->itemsPerPage;
    }

    public function getTotalItems(): float
    {
        return (float) $this->totalItems;
    }

    public function count(): int
    {
        return \count($this->items);
    }

    /**
     * @return \ArrayIterator<int, WalletTransactionView>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }
}
