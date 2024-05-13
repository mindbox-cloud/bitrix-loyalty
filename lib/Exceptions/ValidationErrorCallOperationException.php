<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Exceptions;

use Mindbox\DTO\V3\Responses\ValidationMessageResponseCollection;

class ValidationErrorCallOperationException extends IntegrationLoyaltyException
{
    protected ?ValidationMessageResponseCollection $validationMessage = null;

    public function __construct(
        string $message = "",
        int $code = 0,
        ?\Throwable $previous = null,
        ?string $operationName = null,
        ?ValidationMessageResponseCollection $validationMessage = null,
    ) {
        parent::__construct(
            message: $message,
            code: $code,
            previous: $previous,
        );

        $this->setValidationMessage($validationMessage);
    }

    public function getValidationMessage(): ?ValidationMessageResponseCollection
    {
        return $this->validationMessage;
    }

    public function setValidationMessage(?ValidationMessageResponseCollection $validationMessage): void
    {
        $this->validationMessage = $validationMessage;
    }
}