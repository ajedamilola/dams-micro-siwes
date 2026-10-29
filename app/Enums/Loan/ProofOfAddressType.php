<?php

namespace App\Enums\Loan;

enum ProofOfAddressType: string
{
    case UtilityBill = 'utility_bill';
    case BankStatement = 'bank_statement';
    case TenancyAgreement = 'tenancy_agreement';
    case LocalGovtLetter = 'local_govt_letter';
    case EmployerLetter = 'employer_letter';
}
