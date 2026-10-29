<?php

namespace App\Enums\Loan;

enum RiskRating: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
}
