<?php

namespace App\Enums\Loan;

enum MeansOfIdType: string
{
    case NationalId = 'national_id';
    case DriversLicense = 'drivers_license';
    case IntlPassport = 'intl_passport';
    case VotersCard = 'voters_card';
    case CacCertificate = 'cac_certificate';
}
