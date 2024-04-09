<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Operations;

use Mindbox\DTO\V3\Requests\CustomerRequestDTO;
use Mindbox\Exceptions\MindboxClientException;
use Mindbox\Exceptions\MindboxUnavailableException;
use Mindbox\Loyalty\Api;
use Mindbox\Loyalty\Exceptions\ErrorCallOperationException;
use Mindbox\Loyalty\Models\Customer;

class CheckCustomer extends AbstractOperation
{
    /**
     * @param CustomerRequestDTO $dto
     * @return bool
     * @throws ErrorCallOperationException
     */
    public function execute(CustomerRequestDTO $dto): bool
    {
        try {
            $client = $this->api();

            $response = $client->customer()
                ->checkCustomer(
                    customer: $dto,
                    operationName: $this->getOperation(),
                    addDeviceUUID: false
                )->sendRequest();

            //todo операция возвращает неверный ответ, нет отрицательного

            if ($response->getResult()->getStatus() === 'Success') {
                return true;
            }


        } catch (MindboxUnavailableException $e) {
            // todo тут нужно будет делать ретрай отправки на очереди
        } catch (MindboxClientException $e) {
            // todo log this or log service?

            throw new ErrorCallOperationException(
                message: sprintf('The operation %s failed', $this->getOperation()),
                previous: $e,
                operationName: $this->getOperation()
            );
        }

        return false;
    }

    public function operation(): string
    {
        return 'CheckCustomer';
    }

    public function customOperation(): mixed
    {
        //return 'BitrixCheckCustomer';
        return null;
    }
}