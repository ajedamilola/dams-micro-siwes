<?php

namespace App\Enums\Loan;

enum InterestMethod: string
{
    case Flat = 'flat';
    case Reducing = 'reducing';
}
