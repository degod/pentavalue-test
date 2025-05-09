<?php

namespace App\Repositories;

interface OrderRepositoryInterface
{
    public function create(array $data): array;

    public function getTotalRevenue(): float;
    public function getTopProducts(int $limit = 10): array;
    public function getRevenueLastMinute(): float;
    public function getOrdersLastMinute(): int;
}
