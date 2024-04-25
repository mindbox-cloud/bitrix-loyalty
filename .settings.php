<?php

return [
    'services' => [
		'value' => [
			'mindboxLoyalty.registerCustomer' => [
                'constructor' => fn() => new \Mindbox\Loyalty\Operations\RegisterCustomer()
            ],
            'mindboxLoyalty.checkCustomer' => [
                'constructor' => fn() => new \Mindbox\Loyalty\Operations\CheckCustomer()
            ],
            'mindboxLoyalty.authorizeCustomer' => [
                'constructor' => fn() => new \Mindbox\Loyalty\Operations\AuthorizeCustomer()
            ],
            'mindboxLoyalty.editCustomer' => [
                'constructor' => fn() => new \Mindbox\Loyalty\Operations\EditCustomer()
            ],
            'mindboxLoyalty.getCustomerInfo' => [
                'constructor' => fn() => new \Mindbox\Loyalty\Operations\GetCustomerInfo()
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
            'mindboxLoyalty.feedGenerator' => [
                'constructor' => fn() => new \Mindbox\Loyalty\Feed\YmlFeedMindbox()
            ],
            'mindboxLoyalty.feedCatalogRepository' => [
                'constructor' => fn() => new \Mindbox\Loyalty\Feed\CatalogRepository()
            ],
            'mindboxLoyalty.calculateAuthorizedCart' => [
                'constructor' => fn() => new \Mindbox\Loyalty\Operations\CalculateAuthorizedCart()
            ],
            'mindboxLoyalty.calculateAuthorizedCartAdmin' => [
                'constructor' => fn() => new \Mindbox\Loyalty\Operations\CalculateAuthorizedCartAdmin()
            ],
            'mindboxLoyalty.calculateUnauthorizedCart' => [
                'constructor' => fn() => new \Mindbox\Loyalty\Operations\CalculateUnauthorizedCart()
            ],
            'mindboxLoyalty.createAuthorizedOrder' => [
                'constructor' => fn() => new \Mindbox\Loyalty\Operations\CreateAuthorizedOrder()
            ],
            'mindboxLoyalty.createAuthorizedOrderAdmin' => [
                'constructor' => fn() => new \Mindbox\Loyalty\Operations\CreateAuthorizedOrderAdmin()
            ],
            'mindboxLoyalty.createUnauthorizedOrder' => [
                'constructor' => fn() => new \Mindbox\Loyalty\Operations\CreateUnauthorizedOrder()
            ],
            'mindboxLoyalty.changeStatus' => [
                'constructor' => fn() => new \Mindbox\Loyalty\Operations\ChangeStatus()
            ],
            'mindboxLoyalty.saveOfflineOrder' => [
                'constructor' => fn() => new \Mindbox\Loyalty\Operations\SaveOfflineOrder()
            ],
		],
		'readonly' => true,
	],
];

