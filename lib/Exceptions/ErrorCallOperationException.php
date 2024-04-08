<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Exceptions;

class ErrorCallOperationException extends \Exception
{
    protected ?string $operationName = null;

    public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null, ?string $operationName = null)
    {
        parent::__construct($message, $code, $previous);

        $this->setOperationName($operationName);
    }

    public function getOperationName(): ?string
    {
        return $this->operationName;
    }

    public function setOperationName(?string $operationName): void
    {
        $this->operationName = $operationName;
    }


}