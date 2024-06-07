<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Models;

use Bitrix\Main\Entity\Query\Join;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\Type\Date;
use Bitrix\Main\UserPhoneAuthTable;
use Bitrix\Main\UserTable;
use Mindbox\Loyalty\Support\Settings;
use Mindbox\Loyalty\Support\SettingsFactory;

class Customer
{
    protected int $userId;
    protected string $testPrefix = 'test-';

    protected array $data = [
        'NAME' => null,
        'SECOND_NAME' => null,
        'LAST_NAME' => null,
        'EMAIL' => null,
        'PERSONAL_PHONE' => null,
        'PERSONAL_MOBILE' => null,
        'USER_PHONE_AUTH' => null,
        'PERSONAL_BIRTHDAY' => null,
        'PERSONAL_GENDER' => null,
    ];

    protected Settings $settings;

    public function __construct(int $userId)
    {
        $this->userId = $userId;
        $this->settings = SettingsFactory::create();

        $this->load();
    }

    protected function load(): void
    {
        // todo тут возможно нужно добавить исключение, если клиент не найден
        $userFieldsMatch = $this->settings->getUserFieldsMatch();

        $selectFields = [
            'NAME',
            'SECOND_NAME',
            'LAST_NAME',
            'EMAIL',
            'PERSONAL_PHONE',
            'PERSONAL_MOBILE',
            'PERSONAL_BIRTHDAY',
            'PERSONAL_GENDER',
            'USER_PHONE_AUTH' => 'PHONE_AUTH.PHONE_NUMBER'
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

    public function setEmail(string $value): self
    {
        $clone = clone $this;
        $clone->data['EMAIL'] = $value;

        return $clone;
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
            case $this->data['USER_PHONE_AUTH']:
                $value = $this->data['USER_PHONE_AUTH'];
                break;
        }

        return $value;
    }

    public function setMobilePhone(string $value): self
    {
        $clone = clone $this;
        $clone->data['PERSONAL_PHONE'] = $value;

        return $clone;
    }

    public function getDto(): \Mindbox\DTO\V3\Requests\CustomerRequestDTO
    {
        return new \Mindbox\DTO\V3\Requests\CustomerRequestDTO(array_filter([
            'email' => $this->getEmail(),
            'lastName' => $this->getLastName(),
            'middleName' => $this->getSecondName(),
            'firstName' => $this->getFirstName(),
            'mobilePhone' => $this->getMobilePhone(),
            'birthDate' => $this->getBirthday()?->format('Y-m-d H:i:s'),
            'sex' => $this->getGender(),
            'ids' => $this->getIds()
        ]));
    }

    public function getData(): array
    {
        return array_filter([
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
            $this->settings->getExternalUserId() => $this->prepareId((string)$this->getId())
        ];
    }

    protected function prepareId(mixed $value): string
    {
        return ($this->settings->isTestMode()) ? $this->testPrefix . $value : $value;
    }
}