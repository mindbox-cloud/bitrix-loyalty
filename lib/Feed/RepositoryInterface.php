<?php

namespace Mindbox\Loyalty\Feed;

interface RepositoryInterface
{
    public function getProducts(): \Iterator;

    public function getCurrencies(): array;

    public function getCategories(): array;

    public function getBasePriceId(): int;

    public function getProductGroups(int $productId): array;
}