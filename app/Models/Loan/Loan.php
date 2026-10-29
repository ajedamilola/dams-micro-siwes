<?php

namespace App\Models\Loan;

use App\Models\Company;
use App\Models\GeneralLedger\Account;
use App\Models\GeneralLedger\JournalTransaction;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Loan extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'principal' => 'decimal:2',
            'annual_interest_rate' => 'decimal:4',
            'processing_fee' => 'decimal:2',
            'total_interest' => 'decimal:2',
            'total_repayable' => 'decimal:2',
            'disbursement_date' => 'date',
            'maturity_date' => 'date',
            'first_due_date' => 'date',
            'approved_at' => 'datetime',
            'disbursed_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function product()
    {
        return $this->belongsTo(LoanProduct::class, 'loan_product_id');
    }

    public function customer()
    {
        return $this->belongsTo(LoanCustomer::class, 'loan_customer_id');
    }

    public function schedules()
    {
        return $this->hasMany(LoanSchedule::class, 'loan_id')->orderBy('installment_no');
    }

    public function repayments()
    {
        return $this->hasMany(LoanRepayment::class, 'loan_id')->orderBy('payment_date');
    }

    public function accruals()
    {
        return $this->hasMany(LoanAccrual::class, 'loan_id');
    }

    public function disbursementAccount()
    {
        return $this->belongsTo(Account::class, 'disbursement_account_id');
    }

    public function disbursementJournal()
    {
        return $this->belongsTo(JournalTransaction::class, 'disbursement_journal_id');
    }

    public function outstanding(): string
    {
        $due = $this->schedules->sum(fn ($s) => bcadd(bcadd((string) $s->principal_due, (string) $s->interest_due, 2), bcadd((string) $s->fee_due, (string) $s->penalty_due, 2), 2));
        $paid = $this->schedules->sum(fn ($s) => bcadd(bcadd((string) $s->principal_paid, (string) $s->interest_paid, 2), bcadd((string) $s->fee_paid, (string) $s->penalty_paid, 2), 2));

        return bcsub($due, $paid, 2);
    }
}
