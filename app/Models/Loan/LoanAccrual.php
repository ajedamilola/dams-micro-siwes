<?php

namespace App\Models\Loan;

use App\Models\GeneralLedger\JournalTransaction;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class LoanAccrual extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'accrual_date' => 'date',
            'interest_amount' => 'decimal:2',
        ];
    }

    public function loan()
    {
        return $this->belongsTo(Loan::class, 'loan_id');
    }

    public function schedule()
    {
        return $this->belongsTo(LoanSchedule::class, 'loan_schedule_id');
    }

    public function journal()
    {
        return $this->belongsTo(JournalTransaction::class, 'journal_transaction_id');
    }
}
