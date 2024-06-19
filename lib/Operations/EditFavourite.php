<?php

declare(strict_types=1);

namespace Mindbox\Loyalty\Operations;

class EditFavourite extends EditProductList
{
    protected function operation(): string
    {
        return 'EditFavourite';
    }
}