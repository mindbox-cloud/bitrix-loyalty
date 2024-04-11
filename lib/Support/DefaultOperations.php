<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Support;

final class DefaultOperations
{
    public static function getMap(): array
    {
        return [
            'CheckCustomer',
            'RegisterCustomer',
            'EditCustomer',
            'AuthorizeCustomer',
            'SendConfirmationCode',
            'ConfirmMobilePhone',
            'SendMobilePhoneAuthorizationCode',
            'CheckMobilePhoneAuthorizationCode',
            'CalculateCart',
            'CalculateAuthorizedCart',
            'CalculateUnauthorizedCart',
            'CheckCustomerActive',
            'CreateAuthorizedOrder',
            'ChangeStatus',
            'UpdateOrderStatus',
            'UpdateOrderStatus',
        ];
    }
}
