<?php

namespace App\Enums\Loan;

enum LoanStatus: string
{
    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Active = 'active';
    case Closed = 'closed';
    case Rejected = 'rejected';
    case WrittenOff = 'written_off';
}
