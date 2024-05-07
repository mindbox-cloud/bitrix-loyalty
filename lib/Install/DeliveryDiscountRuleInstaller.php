<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Install;

use Bitrix\Main\Loader;
use Mindbox\Loyalty\Discount\BasketRuleAction;
use Mindbox\Loyalty\Discount\DeliveryRuleAction;

class DeliveryDiscountRuleInstaller implements InstallerInterface
{
    protected static $discountName = 'MindboxDeliveryDiscount';

    protected static $discountXmlId = 'MINDBOX_DELIVERY';

    private string $siteId;

    public function __construct(string $siteId)
    {
        Loader::IncludeModule('sale');

        $this->siteId = $siteId;
    }

    public function up()
    {
        Loader::IncludeModule('currency');

        $id = self::getIdDiscountRule($this->siteId);

        if ($id > 0) {
            return;
        }

        $discountFields = [
            'LID' => $this->siteId,
            'NAME' => self::$discountName,
            'LAST_DISCOUNT' => 'Y',
            'LAST_LEVEL_DISCOUNT' => 'Y',
            'PRIORITY' => 1,
            'SORT' => 10000,
            'XML_ID' => self::$discountXmlId,
            'ACTIVE' => 'Y',
            'CURRENCY' => \Bitrix\Currency\CurrencyManager::getBaseCurrency(),
            'USER_GROUPS' => [2],
            'ACTIONS' => [
                'CLASS_ID' => 'CondGroup',
                'DATA' =>
                    [
                        'All' => 'AND',
                    ],
                'CHILDREN' =>
                    [
                        0 => [
                            'CLASS_ID' => DeliveryRuleAction::GetControlID(),
                            'DATA' => [DeliveryRuleAction::INPUT_NAME => DeliveryRuleAction::INPUT_NAME],
                            'CHILDREN' => [],
                        ],
                    ],
            ],
            'CONDITIONS' => [
                'CLASS_ID' => 'CondGroup',
                'DATA' =>
                    [
                        'All' => 'AND',
                        'True' => 'True',
                    ],
                'CHILDREN' => []
            ]
        ];

        $result = \CSaleDiscount::Add($discountFields);

        return $result;
    }

    public function down()
    {
        $id = self::getIdDiscountRule($this->siteId);

        if ($id > 0) {
            \Bitrix\Sale\Internals\DiscountTable::delete($id);
        }
    }

    private static function getIdDiscountRule(string $siteId): int
    {
        $iterator = \Bitrix\Sale\Internals\DiscountTable::getList([
            'filter' => ['XML_ID' => self::$discountXmlId, 'LID' => $siteId],
            'select' => ['ID']
        ]);

        if ($discount = $iterator->Fetch()) {
            return (int) $discount['ID'];
        }

        return 0;
    }
}