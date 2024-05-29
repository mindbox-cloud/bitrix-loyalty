<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Operations;

class EditCart extends EditProductList
{
    protected function operation(): string
    {
        return 'EditCart';
    }
}