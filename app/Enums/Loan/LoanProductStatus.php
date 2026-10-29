<?php

namespace App\Enums\Loan;

enum LoanProductStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
