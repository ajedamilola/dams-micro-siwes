<?php

namespace App\Enums\Loan;

enum CustomerStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Blacklisted = 'blacklisted';
}
