<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Install;

use Bitrix\Main\Loader;
use Mindbox\Loyalty\Discount\BasketRuleAction;

class DiscountRuleInstaller implements InstallerInterface
{
    protected static $discountName = 'MindboxBasket';

    protected static $discountXmlId = 'MINDBOX_BASKET';

    private string $siteId;

    public function __construct(string $siteId)
    {
        Loader::IncludeModule('sale');

        $this->siteId = $siteId;
    }

    public function up()
    {
        Loader::IncludeModule('currency');

        $id = self::getIdMindboxBasketRule($this->siteId);
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
                            'CLASS_ID' => BasketRuleAction::GetControlID(),
                            'DATA' => [BasketRuleAction::INPUT_NAME => BasketRuleAction::INPUT_NAME],
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

        \CSaleDiscount::Add($discountFields);
    }

    public function down()
    {
        $id = self::getIdMindboxBasketRule($this->siteId);

        if ($id > 0) {
            \Bitrix\Sale\Internals\DiscountTable::delete($id);
        }
    }

    private static function getIdMindboxBasketRule(string $siteId): int
    {
        $iterator = \Bitrix\Sale\Internals\DiscountTable::getList([
            'filter' => ['XML_ID' => self::$discountXmlId, 'LID' => $siteId],
            'select' => ['ID']
        ]);

        if ($discount = $iterator->Fetch()) {
            return (int)$discount['ID'];
        }

        return 0;
    }
}