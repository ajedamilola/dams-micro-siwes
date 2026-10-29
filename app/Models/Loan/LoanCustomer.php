<?php

namespace App\Models\Loan;

use App\Models\Company;
use App\Models\GeneralLedger\Account;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoanCustomer extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'id_expiry_date' => 'date',
            'is_pep' => 'boolean',
            'monthly_income' => 'decimal:2',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function documents()
    {
        return $this->hasMany(LoanCustomerDocument::class, 'loan_customer_id');
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->customer_type === 'corporate') {
            return (string) ($this->business_name ?: trim("{$this->first_name} {$this->last_name}"));
        }

        return trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
    }
}
