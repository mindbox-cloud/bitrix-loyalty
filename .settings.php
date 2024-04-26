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
            'mindboxLoyalty.feedGenerator' => [
                'constructor' => fn() => new \Mindbox\Loyalty\Feed\YmlFeedMindbox()
            ],
            'mindboxLoyalty.feedCatalogRepository' => [
                'constructor' => fn() => new \Mindbox\Loyalty\Feed\CatalogRepository()
            ],
		],
		'readonly' => true,
	],
];

