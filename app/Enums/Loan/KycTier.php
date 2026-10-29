<?php

namespace App\Enums\Loan;

enum KycTier: string
{
    case Tier1 = 'tier1';
    case Tier2 = 'tier2';
    case Tier3 = 'tier3';

    /**
     * CBN three-tiered KYC summary. Limits reflect the 2016 upward review
     * (Tier1: 50k single / 300k cumulative, Tier2: 100k single / 500k
     * cumulative) plus the Dec 2023 amendment mandating BVN and/or NIN.
     *
     * @return array<string, mixed>
     */
    public function requirements(): array
    {
        return match ($this) {
            self::Tier1 => [
                'label' => 'Tier 1 - Low value',
                'id_requirement' => 'BVN or NIN plus basic customer information (name, phone, date and place of birth, gender, address, passport photograph). No document verification required at opening.',
                'single_deposit_limit' => 50000,
                'cumulative_balance_limit' => 300000,
                'daily_transaction_limit' => 30000,
            ],
            self::Tier2 => [
                'label' => 'Tier 2 - Medium value',
                'id_requirement' => 'BVN and NIN plus evidence of basic information with verification against official databases.',
                'single_deposit_limit' => 100000,
                'cumulative_balance_limit' => 500000,
                'daily_transaction_limit' => null,
            ],
            self::Tier3 => [
                'label' => 'Tier 3 - Full KYC',
                'id_requirement' => 'BVN and NIN plus valid means of identification (national ID, driver licence, international passport or voter card), verified proof of address, passport photograph and signature. Required for all loan customers.',
                'single_deposit_limit' => null,
                'cumulative_balance_limit' => null,
                'daily_transaction_limit' => null,
            ],
        };
    }
}
