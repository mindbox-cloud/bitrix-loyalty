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
		],
		'readonly' => true,
	],
];

