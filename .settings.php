<?php

use Mindbox\Loyalty\Operations\GetCustomerBalanceHistory;

return [
    'services' => [
		'value' => [
			'mindboxLoyalty.registerCustomer' => [
                'constructor' => fn() => new \Mindbox\Loyalty\Operations\RegisterCustomer()
            ],
            'mindboxLoyalty.checkCustomer' => [
                'constructor' => fn() => new \Mindbox\Loyalty\Operations\CheckCustomer()
            ],
            'mindboxLoyalty.getCustomer' => [
                'constructor' => fn() => new \Mindbox\Loyalty\Operations\GetCustomer()
            ],
            'mindboxLoyalty.authorizeCustomer' => [
                'constructor' => fn() => new \Mindbox\Loyalty\Operations\AuthorizeCustomer()
            ],
            'mindboxLoyalty.editCustomer' => [
                'constructor' => fn() => new \Mindbox\Loyalty\Operations\EditCustomer()
            ],
            'mindboxLoyalty.syncCustomer' => [
                'constructor' => fn() => new \Mindbox\Loyalty\Operations\SyncCustomer()
            ],
            'mindboxLoyalty.checkMobilePhoneCode' => [
                'constructor' => fn() => new \Mindbox\Loyalty\Operations\CheckMobilePhoneCode()
            ],
            'mindboxLoyalty.confirmMobilePhone' => [
                'constructor' => fn() => new \Mindbox\Loyalty\Operations\ConfirmMobilePhone()
            ],
            'mindboxLoyalty.sendMobilePhoneCode' => [
                'constructor' => fn() => new \Mindbox\Loyalty\Operations\SendMobilePhoneCode()
            ],
            'mindboxLoyalty.sendMobilePhoneCodeToEdit' => [
                'constructor' => fn() => new \Mindbox\Loyalty\Operations\SendMobilePhoneCodeToEdit()
            ],
            'mindboxLoyalty.confirmEmail' => [
                'constructor' => fn() => new \Mindbox\Loyalty\Operations\ConfirmEmail()
            ],
            'mindboxLoyalty.feedGenerator' => [
                'constructor' => fn() => new \Mindbox\Loyalty\Feed\YmlFeedMindbox()
            ],
            'mindboxLoyalty.feedCatalogRepository' => [
                'constructor' => fn() => new \Mindbox\Loyalty\Feed\CatalogRepository()
            ],
            'mindboxLoyalty.subscribeCustomer' => [
                'constructor' => fn() => new \Mindbox\Loyalty\Operations\SubscribeCustomer()
            ],
            'mindboxLoyalty.calculateAuthorizedCart' => [
                'constructor' => fn() => new \Mindbox\Loyalty\Operations\CalculateAuthorizedCart()
            ],
            'mindboxLoyalty.calculateCartAdmin' => [
                'constructor' => fn() => new \Mindbox\Loyalty\Operations\CalculateCartAdmin()
            ],
            'mindboxLoyalty.calculateUnauthorizedCart' => [
                'constructor' => fn() => new \Mindbox\Loyalty\Operations\CalculateUnauthorizedCart()
            ],
            'mindboxLoyalty.createAuthorizedOrder' => [
                'constructor' => fn() => new \Mindbox\Loyalty\Operations\CreateAuthorizedOrder()
            ],
            'mindboxLoyalty.createOrderAdmin' => [
                'constructor' => fn() => new \Mindbox\Loyalty\Operations\CreateOrderAdmin()
            ],
            'mindboxLoyalty.createUnauthorizedOrder' => [
                'constructor' => fn() => new \Mindbox\Loyalty\Operations\CreateUnauthorizedOrder()
            ],
            'mindboxLoyalty.changeStatus' => [
                'constructor' => fn() => new \Mindbox\Loyalty\Operations\ChangeStatus()
            ],
            'mindboxLoyalty.changeStatusAdmin' => [
                'constructor' => fn() => new \Mindbox\Loyalty\Operations\ChangeStatusAdmin()
            ],
            'mindboxLoyalty.saveOfflineOrder' => [
                'constructor' => fn() => new \Mindbox\Loyalty\Operations\SaveOfflineOrder()
            ],
            'mindboxLoyalty.editCart' => [
                'constructor' => fn() => new \Mindbox\Loyalty\Operations\EditCart()
            ],
            'mindboxLoyalty.clearCart' => [
                'constructor' => fn() => new \Mindbox\Loyalty\Operations\ClearCart()
            ],
            'mindboxLoyalty.clearFavourite' => [
                'constructor' => fn() => new \Mindbox\Loyalty\Operations\ClearFavourite()
            ],
            'mindboxLoyalty.editFavourite' => [
                'constructor' => fn() => new \Mindbox\Loyalty\Operations\EditFavourite()
            ],
            'mindboxLoyalty.setFavourite' => [
                'constructor' => fn() => new \Mindbox\Loyalty\Operations\SetFavourite()
            ],
            'mindboxLoyalty.getCustomerPoints' => [
                'constructor' => fn() => new \Mindbox\Loyalty\Operations\GetCustomerPoints()
            ],
            'mindboxLoyalty.getCustomerBalanceHistory' => [
                'constructor' => fn() => new \Mindbox\Loyalty\Operations\GetCustomerBalanceHistory()
            ],
            'mindboxLoyalty.getCustomerLoyaltyLevel' => [
                'constructor' => fn() => new \Mindbox\Loyalty\Operations\GetCustomerLoyaltyLevel()
            ],
		],
		'readonly' => true,
	],
    'controllers' => [
        'value' => [
            'namespaces' => [
                '\\Mindbox\\Loyalty\\Controllers' => 'calculate',
            ],
        ],
        'readonly' => true,
    ],
];

