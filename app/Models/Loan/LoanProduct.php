<?php

namespace App\Models\Loan;

use App\Models\Company;
use App\Models\GeneralLedger\Account;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class LoanProduct extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'annual_interest_rate' => 'decimal:4',
            'min_principal' => 'decimal:2',
            'max_principal' => 'decimal:2',
            'processing_fee_rate' => 'decimal:4',
            'processing_fee_flat' => 'decimal:2',
            'penalty_rate_daily' => 'decimal:4',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function mappings()
    {
        return $this->hasMany(LoanProductAccount::class, 'loan_product_id');
    }

    public function loans()
    {
        return $this->hasMany(Loan::class, 'loan_product_id');
    }

    public function accountFor(string $operationType): ?Account
    {
        $mapping = $this->mappings->firstWhere('operation_type', $operationType)
            ?? $this->mappings()->where('operation_type', $operationType)->first();

        return $mapping?->account;
    }
}
