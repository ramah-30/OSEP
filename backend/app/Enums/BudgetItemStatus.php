<?php

namespace App\Enums;

enum BudgetItemStatus: string
{
    case Planned = 'planned';
    case Committed = 'committed';
    case Paid = 'paid';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
