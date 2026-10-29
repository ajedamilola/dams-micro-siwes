<?php

namespace App\Enums\Loan;

enum OperationType: string
{
    case PrincipalReceivable = 'principal_receivable';
    case InterestReceivable = 'interest_receivable';
    case InterestIncome = 'interest_income';
    case FeeIncome = 'fee_income';
    case PenaltyReceivable = 'penalty_receivable';
    case PenaltyIncome = 'penalty_income';

    /**
     * @return array<string, array{balance_type: string, categories: array<int, string>}>
     */
    public static function expectations(): array
    {
        return [
            self::PrincipalReceivable->value => ['balance_type' => 'debit', 'categories' => ['AccountsReceivable']],
            self::InterestReceivable->value => ['balance_type' => 'debit', 'categories' => ['AccountsReceivable', 'OtherCurrentAssets']],
            self::InterestIncome->value => ['balance_type' => 'credit', 'categories' => ['OperatingRevenue', 'OtherIncome']],
            self::FeeIncome->value => ['balance_type' => 'credit', 'categories' => ['OperatingRevenue', 'OtherIncome']],
            self::PenaltyReceivable->value => ['balance_type' => 'debit', 'categories' => ['AccountsReceivable', 'OtherCurrentAssets']],
            self::PenaltyIncome->value => ['balance_type' => 'credit', 'categories' => ['OperatingRevenue', 'OtherIncome']],
        ];
    }
}
