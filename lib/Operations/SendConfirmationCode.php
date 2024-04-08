<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Operations;

use Mindbox\DTO\V3\Requests\CustomerRequestDTO;
use Mindbox\Loyalty\Models\Customer;

class SendConfirmationCode extends AbstractOperation
{
    public function execute(string $phone)
    {
        $dto = new CustomerRequestDTO([
            'mobilePhone' => $phone,
        ]);

        $result = $this->api()->customer()->sendAuthorizationCode(
            $dto,
            $this->getOperation(),
        )->sendRequest();

        echo '<pre>'; print_r($result); echo '</pre>';
    }

    public function operation(): string
    {
        return 'SendConfirmationCode';
    }
}