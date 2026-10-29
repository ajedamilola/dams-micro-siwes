<?php

namespace App\Models\Loan;

use App\Models\GeneralLedger\Account;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class LoanProductAccount extends Model
{
    use HasUuids;

    protected $guarded = [];

    public function product()
    {
        return $this->belongsTo(LoanProduct::class, 'loan_product_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }
}
