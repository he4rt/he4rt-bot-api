<?php

namespace Heart\Shared\Infrastructure;

use Heart\Shared\Domain\Paginator as PaginatorInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class Paginator extends LengthAwarePaginator implements PaginatorInterface
{
    public function __construct($items, $total, $perPage, $currentPage = null, array $options = [])
    {
        parent::__construct($items, $total, $perPage, $currentPage, $options);
    }
}
