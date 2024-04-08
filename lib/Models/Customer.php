<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Models;

use Bitrix\Main\Entity\Query\Join;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\Type\Date;
use Bitrix\Main\UserPhoneAuthTable;
use Bitrix\Main\UserTable;
use Mindbox\Loyalty\Support\Settings;

class Customer
{
    protected int $userId;

    protected array $data = [
        'NAME' => null,
        'SECOND_NAME' => null,
        'LAST_NAME' => null,
        'EMAIL' => null,
        'PERSONAL_PHONE' => null,
        'PHONE_AUTH' => null,
        'PERSONAL_BIRTHDAY' => null,
        'PERSONAL_GENDER' => null,
    ];

    public function __construct(int $userId)
    {
        $this->userId = $userId;
        $this->load();
    }

    protected function load(): void
    {
        // todo тут возможно нужно добавить исключение, если клиент не найден
        $userFieldsMatch = Settings::getInstance()->getUserFieldsMatch();

        $selectFields = [
            'NAME',
            'SECOND_NAME',
            'LAST_NAME',
            'EMAIL',
            'PERSONAL_PHONE',
            'PERSONAL_BIRTHDAY',
            'PERSONAL_GENDER',
            'PHONE_AUTH'
        ];

        if (!empty($userFieldsMatch)) {
            $selectFields = array_merge($selectFields, array_keys($userFieldsMatch));
        }

        $userData = UserTable::getList([
            'filter' => [
                '=ID' => $this->getUserId(),
            ],
            'limit' => 1,
            'select' => $selectFields,
        ])->fetch();

        if (!empty($userData)) {
            $this->data = array_replace_recursive($this->data, $userData);
        }
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getFirstName(): ?string
    {
        return $this->data['NAME'];
    }

    public function getMiddleName(): ?string
    {
        return $this->data['SECOND_NAME'];
    }

    public function getLastName(): ?string
    {
        return $this->data['LAST_NAME'];
    }

    public function getSecondName(): ?string
    {
        return $this->data['SECOND_NAME'];
    }

    public function getEmail(): ?string
    {
        return $this->data['EMAIL'];
    }

    public function getGender(): ?string
    {
        $return = null;

        if (!empty($this->data['PERSONAL_GENDER'])) {
            $return = ($this->data['PERSONAL_GENDER']) === 'M' ? 'male' : 'female';
        }

        return $return;
    }

    public function getBirthday(): ?Date
    {
        return $this->data['PERSONAL_BIRTHDAY'];
    }

    public function getMobilePhone(): ?string
    {
        $value = null;

        switch (true) {
            case $this->data['PERSONAL_PHONE']:
                $value = $this->data['PERSONAL_PHONE'];
                break;
            case $this->data['PERSONAL_MOBILE']:
                $value = $this->data['PERSONAL_MOBILE'];
                break;
            case $this->data['PHONE_AUTH']:
                $value = $this->data['PHONE_AUTH'];
                break;
        }

        return $value;
    }

    public function getDto(): \Mindbox\DTO\V3\Requests\CustomerRequestDTO
    {
        // todo тут нужно бы добавить блок id пользователя
        return new \Mindbox\DTO\V3\Requests\CustomerRequestDTO([
            'email' => $this->getEmail(),
            'lastName' => $this->getLastName(),
            'middleName' => $this->getSecondName(),
            'firstName' => $this->getFirstName(),
            'mobilePhone' => $this->getMobilePhone(),
            'birthDate' => $this->getBirthday()?->format('Y-m-d H:i:s'),
            'sex' => $this->getGender(),
            'ids' => $this->getIds()
        ]);
    }

    public function getId(): int
    {
        return $this->userId;
    }

    public function getIds(): array
    {
        return [
            Settings::getInstance()->getExternalUserId() => $this->getId()
        ];
    }
}