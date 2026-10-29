<?php

namespace App\Enums\GeneralLedger;

enum AccountType
{
    case Asset;
    case Expense;
    case Liability;
    case Revenue;
    case Equity; // Capital
}
