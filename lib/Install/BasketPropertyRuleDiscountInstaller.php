<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Install;

use Bitrix\Main\Loader;
use Mindbox\Loyalty\Discount\BasketPropertyRuleDiscount;
use Bitrix\Main\Localization\Loc;

class BasketPropertyRuleDiscountInstaller implements InstallerInterface
{
    protected static $discountXmlId = 'MINDBOX_BASKET_PROPERTY';
    private string $siteId;

    public function __construct(string $siteId)
    {
        Loader::IncludeModule('sale');

        $this->siteId = $siteId;
    }

    public function up()
    {
        $id = self::getIdDiscountRule($this->siteId);
        if ($id > 0) {
            return;
        }

        $discountFields = [
            'LID' => $this->siteId,
            'NAME' => self::getDiscountName(),
            'ACTIVE_FROM' => '',
            'ACTIVE_TO' => '',
            'ACTIVE' => 'Y',
            'SORT' => '9999',
            'PRIORITY' => '1',
            'LAST_DISCOUNT' => 'Y',
            'LAST_LEVEL_DISCOUNT' => 'Y',
            'XML_ID' => self::$discountXmlId,
            'USER_GROUPS' => [2],
            'CONDITIONS' => [
                'CLASS_ID' => 'CondGroup',
                'DATA' => [
                    'All' => 'AND',
                    'True' => 'True',
                ],
                'CHILDREN' => [],
            ],
            'ACTIONS' => [
                'CLASS_ID' => 'CondGroup',
                'DATA' => [
                    'All' => 'AND',
                ],
                'CHILDREN' => [
                    0 => [
                        'CLASS_ID' => BasketPropertyRuleDiscount::GetControlID(),
                        'DATA' => [BasketPropertyRuleDiscount::INPUT_NAME => BasketPropertyRuleDiscount::INPUT_NAME],
                        'CHILDREN' => [],
                    ],
                ],
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

    private static function getDiscountName(): string
    {
        return Loc::getMessage('MINDBOX_LOYALTY_BASKET_PROPERTY_DISCOUNT_NAME');
    }
}