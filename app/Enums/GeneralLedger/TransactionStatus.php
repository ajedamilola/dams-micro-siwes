<?php

namespace App\Enums\GeneralLedger;

enum TransactionStatus: string
{
    case DRAFT = 'DRAFT';
    case SUBMITTED = 'SUBMITTED';
    case POSTED = 'POSTED';
    case REVERSED = 'REVERSED';
    case CANCELLED = 'CANCELLED';

    public function canAdvance(TransactionStatus $nextStatus)
    {
        $eligibleStatuses = match ($this) {
            TransactionStatus::DRAFT => [
                TransactionStatus::SUBMITTED,
                TransactionStatus::CANCELLED,
            ],
            TransactionStatus::SUBMITTED => [
                TransactionStatus::CANCELLED,
                TransactionStatus::POSTED,
            ],
            TransactionStatus::CANCELLED => [],
            TransactionStatus::REVERSED => [],
            TransactionStatus::POSTED => [TransactionStatus::REVERSED]
        };

        return in_array($nextStatus, $eligibleStatuses);
    }

    public static function caseValues()
    {
        $cases = TransactionStatus::cases();
        $arr = [];
        foreach ($cases as $case) {
            array_push($arr, $case->value);
        }

        return $arr;
    }
}
