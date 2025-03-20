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
            'SubscribeCustomer',
            'CheckLoyaltyProgramParticipants',
            'GetCustomerPoints',
            'SendConfirmationCode',
            'SendMobilePhoneCodeToEdit',
            'ConfirmEmail',
            'ConfirmMobilePhone',
            'SendMobilePhoneAuthorizationCode',
            'CheckMobilePhoneAuthorizationCode',
            'CalculateAuthorizedOrder',
            'ManualCalculateOrder',
            'CalculateUnauthorizedOrder',
            'CheckCustomerActive',
            'CreateAuthorizedOrder',
            'ManualCreateOrder',
            'CreateUnauthorizedOrder',
            'ChangeStatus',
            'ManualChangeStatus',
            'SaveOfflineOrder',
            'EditCart',
            'ClearCart',
            'EditFavourite',
            'SetFavourite',
            'ClearFavourite',
            'GetCustomerBalanceHistory',
            'GetCustomerLoyaltyLevel',
        ];
    }
}
