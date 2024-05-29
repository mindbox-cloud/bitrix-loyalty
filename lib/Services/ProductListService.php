<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Services;


use Bitrix\Main\ObjectNotFoundException;
use Mindbox\DTO\V3\Requests\CustomerIdentityRequestDTO;
use Mindbox\DTO\V3\Requests\ProductListItemRequestCollection;
use Mindbox\DTO\V3\Requests\ProductListItemRequestDTO;
use Mindbox\DTO\V3\Requests\ProductRequestDTO;
use Mindbox\Helper;
use Mindbox\Loyalty\Exceptions\ErrorCallOperationException;
use Mindbox\Loyalty\Models\Customer;
use Mindbox\Loyalty\Models\Product;
use Mindbox\Loyalty\Operations\EditCart;
use Mindbox\Loyalty\Operations\EditFavourite;
use Mindbox\Loyalty\Operations\EditProductList;
use Mindbox\Loyalty\Support\Settings;
use Mindbox\Options;

class ProductListService
{
    protected \Bitrix\Main\DI\ServiceLocator $serviceLocator;
    protected Settings $settings;

    public function __construct(Settings $settings)
    {
        $this->serviceLocator = \Bitrix\Main\DI\ServiceLocator::getInstance();
        $this->settings = $settings;
    }

    /**
     * @throws ErrorCallOperationException
     * @throws ObjectNotFoundException
     */
    public function editCart(Product $product, int $quantity = 1, ?Customer $customer = null): void
    {
        /** @var EditCart $operation */
        $operation = $this->serviceLocator->get('mindboxLoyalty.editCart');

        $this->editList(
            operation: $operation,
            product: $product,
            quantity: $quantity,
            customer: $customer
        );
    }

    /**
     * @param Product $product
     * @param int $quantity
     * @param Customer|null $customer
     * @return void
     * @throws ErrorCallOperationException
     * @throws ObjectNotFoundException
     */
    public function editFavourite(Product $product, int $quantity = 1, ?Customer $customer = null): void
    {
        /** @var EditFavourite $operation */
        $operation = $this->serviceLocator->get('mindboxLoyalty.editFavourite');

        $this->editList(
            operation: $operation,
            product: $product,
            quantity: $quantity,
            customer: $customer
        );
    }

    /**
     * @param EditProductList $operation
     * @param Product $product
     * @param int $quantity
     * @param Customer|null $customer
     * @return void
     * @throws ErrorCallOperationException
     */
    public function editList(EditProductList $operation, Product $product, int $quantity = 1, ?Customer $customer = null): void
    {
        $payload = [];

        if (!empty($customer)) {
            $payload['customer']['ids'] = $customer->getIds();
        }

        $payload['setProductCountInList'] = [
            'product' => ['ids' => $product->getIds()],
            'priceOfLine' => $product->getPrice(),
            'count' => $quantity
        ];

        $operation->execute($payload);
    }
}