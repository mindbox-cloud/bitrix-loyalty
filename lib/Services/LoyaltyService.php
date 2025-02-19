<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Services;

use Mindbox\DTO\V3\Requests\CustomerRequestDTO;
use Mindbox\Loyalty\Exceptions\ErrorCallOperationException;
use Mindbox\Loyalty\Models\Customer;
use Mindbox\Loyalty\Operations\GetCustomerLoyaltyLevel;

class LoyaltyService
{
    public function __construct(private array $segments = [])
    {
    }

    public function getCurrentSegmentLoyalty(int $userId): string
    {

        $customer = new Customer($userId);
        $customerDTO = new CustomerRequestDTO([
            'ids' => $customer->getIds()
        ]);

        $segmentExternalIds = array_keys($this->segments);
        $segments = [];
        foreach ($segmentExternalIds as $segmentExternalId) {
            $segments[] = ['ids' => ['externalId' => $segmentExternalId]];
        }

        $operation = new GetCustomerLoyaltyLevel();
        try {
            $response = $operation->execute($customerDTO, $segments);
        } catch (ErrorCallOperationException $e) {
            return current($this->segments);
        }

        $result = $response->getResult();

        if ($result->getStatus() !== 'Success') {
            return current($this->segments);
        }

        if (empty($result->getCustomerSegmentations())) {
            return current($this->segments);
        }

        $segmentations = $result->getCustomerSegmentations();

        $userSegment = null;
        foreach ($segmentations as $segmentation) {
            $segment = $segmentation->getSegment();
            if ($segment) {
                $userSegment = $segment->getIds()['externalId'];
            }
        }

        return $this->getNameBySegment($userSegment);
    }

    protected function getNameBySegment(?string $segmentExternalId): string
    {
        if ($name = $this->segments[$segmentExternalId]) {
            return $name;
        }

        return current($this->segments);
    }
}