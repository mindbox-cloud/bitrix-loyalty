<?php
return [
    'services' => [
		'value' => [
			'mindboxLoyalty.registerCustomer' => [
                'constructor' => fn () => new \Mindbox\Loyalty\Operations\RegisterCustomer()
            ]
		],
		'readonly' => true,
	],
];

