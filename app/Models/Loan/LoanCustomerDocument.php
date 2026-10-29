<?php

namespace App\Models\Loan;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class LoanCustomerDocument extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(LoanCustomer::class, 'loan_customer_id');
    }
}
