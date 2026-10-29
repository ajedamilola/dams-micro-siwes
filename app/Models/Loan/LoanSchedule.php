<?php

namespace App\Models\Loan;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class LoanSchedule extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'principal_due' => 'decimal:2',
            'interest_due' => 'decimal:2',
            'fee_due' => 'decimal:2',
            'penalty_due' => 'decimal:2',
            'total_due' => 'decimal:2',
            'principal_paid' => 'decimal:2',
            'interest_paid' => 'decimal:2',
            'fee_paid' => 'decimal:2',
            'penalty_paid' => 'decimal:2',
            'accrued_interest' => 'decimal:2',
        ];
    }

    public function loan()
    {
        return $this->belongsTo(Loan::class, 'loan_id');
    }

    public function remaining(): string
    {
        $due = bcadd(bcadd((string) $this->principal_due, (string) $this->interest_due, 2), bcadd((string) $this->fee_due, (string) $this->penalty_due, 2), 2);
        $paid = bcadd(bcadd((string) $this->principal_paid, (string) $this->interest_paid, 2), bcadd((string) $this->fee_paid, (string) $this->penalty_paid, 2), 2);

        return bcsub($due, $paid, 2);
    }

    public function isPaid(): bool
    {
        return bccomp($this->remaining(), '0', 2) <= 0;
    }
}
