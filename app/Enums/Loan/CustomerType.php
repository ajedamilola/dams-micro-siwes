<?php

namespace App\Enums\Loan;

enum CustomerType: string
{
    case Individual = 'individual';
    case Corporate = 'corporate';
    case Group = 'group';
}
