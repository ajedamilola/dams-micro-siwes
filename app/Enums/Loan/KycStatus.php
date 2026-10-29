<?php

namespace App\Enums\Loan;

enum KycStatus: string
{
    case Pending = 'pending';
    case InReview = 'in_review';
    case Verified = 'verified';
    case Rejected = 'rejected';
}
