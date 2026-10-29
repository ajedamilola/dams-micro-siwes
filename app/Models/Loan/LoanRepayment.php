<?php

namespace App\Models\Loan;

use App\Models\GeneralLedger\Account;
use App\Models\GeneralLedger\JournalTransaction;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class LoanRepayment extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
            'principal_component' => 'decimal:2',
            'interest_component' => 'decimal:2',
            'fee_component' => 'decimal:2',
            'penalty_component' => 'decimal:2',
        ];
    }

    public function loan()
    {
        return $this->belongsTo(Loan::class, 'loan_id');
    }

    public function paymentAccount()
    {
        return $this->belongsTo(Account::class, 'payment_account_id');
    }

    public function journal()
    {
        return $this->belongsTo(JournalTransaction::class, 'journal_transaction_id');
    }
}
