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
            'mindboxLoyalty.sendMobilePhoneAuthorizationCode' => [
                'constructor' => fn() => new \Mindbox\Loyalty\Operations\SendMobilePhoneAuthorizationCode()
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
		],
		'readonly' => true,
	],
];

