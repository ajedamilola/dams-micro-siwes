<?php

namespace App\Http\Controllers\Loan;

use App\Http\Controllers\Controller;
use App\Models\GeneralLedger\Account;
use App\Models\Loan\Loan;
use App\Models\Loan\LoanCustomer;
use App\Models\Loan\LoanProduct;
use App\Services\Loan\LoanLifecycle;
use App\Support\BusinessDate;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class LoanController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'status' => ['sometimes', 'nullable', 'string', 'max:30'],
            'q' => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);

        $loans = Loan::with(['product', 'customer', 'company'])
            ->when(! empty($data['status'] ?? null), fn ($q) => $q->where('status', $data['status']))
            ->when(! empty($data['q'] ?? null), function ($q) use ($data) {
                $token = strtolower(trim((string) $data['q']));
                $q->where(function ($q) use ($token) {
                    $q->whereRaw('lower(loan_no) like ?', ["%{$token}%"]);
                });
            })
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        return Inertia::render('Loan/Loans/Index', [
            'loans' => $loans->map(fn (Loan $l) => self::presentBrief($l))->values()->all(),
            'products' => LoanProduct::with('company')->where('status', 'active')->orderBy('code')->get()->map(fn (LoanProduct $p) => [
                'id' => $p->id,
                'code' => $p->code,
                'name' => $p->name,
                'company_id' => $p->company_id,
                'company_name' => $p->company?->name,
                'interest_method' => $p->interest_method,
                'annual_interest_rate' => (string) $p->annual_interest_rate,
                'repayment_frequency' => $p->repayment_frequency,
                'min_principal' => (string) $p->min_principal,
                'max_principal' => (string) $p->max_principal,
                'min_tenor' => $p->min_tenor,
                'max_tenor' => $p->max_tenor,
                'processing_fee_rate' => (string) $p->processing_fee_rate,
            ])->values()->all(),
            'filters' => ['status' => $data['status'] ?? '', 'q' => $data['q'] ?? ''],
        ]);
    }

    public function store(Request $request, LoanLifecycle $lifecycle)
    {
        $data = $request->validate([
            'loan_product_id' => ['required', 'string', 'exists:loan_products,id'],
            'loan_customer_id' => ['required', 'string', 'exists:loan_customers,id'],
            'principal' => ['required', 'numeric', 'min:1'],
            'tenor' => ['required', 'integer', 'min:1', 'max:600'],
            'first_due_date' => ['required', 'date', 'after:'.BusinessDate::today()],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $product = LoanProduct::findOrFail($data['loan_product_id']);
        $customer = LoanCustomer::findOrFail($data['loan_customer_id']);

        if ((string) $customer->company_id !== (string) $product->company_id) {
            throw ValidationException::withMessages(['loan_customer_id' => 'Customer and product must belong to the same company.']);
        }
        if ($customer->kyc_status !== 'verified') {
            throw ValidationException::withMessages(['loan_customer_id' => 'Only KYC-verified customers can book loans.']);
        }
        if (! $customer->account_id) {
            throw ValidationException::withMessages(['loan_customer_id' => 'Customer has no linked GL account.']);
        }
        if ($product->status !== 'active') {
            throw ValidationException::withMessages(['loan_product_id' => 'Only active products can book loans.']);
        }
        if ($product->mappings()->count() < 6) {
            throw ValidationException::withMessages(['loan_product_id' => 'Product interfacing is incomplete. Map all six operations first.']);
        }

        $loan = $lifecycle->book($data);

        return redirect()->route('loans.loans.show', ['loan' => $loan->id]);
    }

    public function show(string $loan)
    {
        $loan = Loan::with(['product.mappings.account', 'customer.account', 'company', 'schedules', 'repayments.paymentAccount', 'repayments.journal', 'accruals'])->findOrFail($loan);

        return Inertia::render('Loan/Loans/Show', [
            'loan' => self::present($loan),
        ]);
    }

    public function approve(string $loan, LoanLifecycle $lifecycle)
    {
        $loan = Loan::findOrFail($loan);
        $lifecycle->approve($loan);

        return back();
    }

    public function reject(string $loan, LoanLifecycle $lifecycle)
    {
        $loan = Loan::findOrFail($loan);
        $lifecycle->reject($loan);

        return back();
    }

    public function disburse(Request $request, string $loan, LoanLifecycle $lifecycle)
    {
        $loan = Loan::findOrFail($loan);
        $data = $request->validate([
            'bank_account_id' => ['required', 'string', 'exists:accounts,id'],
            'disbursement_date' => ['required', 'date', 'before_or_equal:'.BusinessDate::today()],
        ]);

        $bank = Account::findOrFail($data['bank_account_id']);
        if (($bank->status ?? 'active') !== 'active' || $bank->subledger_id !== null || Account::where('parent_id', $bank->id)->exists()) {
            throw ValidationException::withMessages(['bank_account_id' => 'Choose an active leaf settlement account.']);
        }
        if (! in_array($bank->category, ['CashAndCashEquivalents', 'OtherCurrentAssets'], true)) {
            throw ValidationException::withMessages(['bank_account_id' => 'Settlement must be a cash/bank or clearing account.']);
        }

        $lifecycle->disburse($loan, $bank->id, $data['disbursement_date']);

        return back();
    }

    public function repay(Request $request, string $loan, LoanLifecycle $lifecycle)
    {
        $loan = Loan::findOrFail($loan);
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_account_id' => ['required', 'string', 'exists:accounts,id'],
            'payment_date' => ['required', 'date', 'before_or_equal:'.BusinessDate::today()],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $bank = Account::findOrFail($data['payment_account_id']);
        if (($bank->status ?? 'active') !== 'active' || $bank->subledger_id !== null || Account::where('parent_id', $bank->id)->exists()) {
            throw ValidationException::withMessages(['payment_account_id' => 'Choose an active leaf settlement account.']);
        }

        $lifecycle->repay($loan, (string) $data['amount'], $bank->id, $data['payment_date'], $data['notes'] ?? null);

        return back();
    }

    /** @return array<string, mixed> */
    private static function presentBrief(Loan $loan): array
    {
        return [
            'id' => $loan->id,
            'loan_no' => $loan->loan_no,
            'status' => $loan->status,
            'principal' => (string) $loan->principal,
            'total_repayable' => (string) $loan->total_repayable,
            'product_code' => $loan->product?->code,
            'product_name' => $loan->product?->name,
            'customer_no' => $loan->customer?->customer_no,
            'customer_name' => $loan->customer?->display_name,
            'maturity_date' => $loan->maturity_date?->toDateString(),
        ];
    }

    /** @return array<string, mixed> */
    private static function present(Loan $loan): array
    {
        $schedules = $loan->relationLoaded('schedules') ? $loan->schedules : $loan->schedules()->orderBy('installment_no')->get();

        return [
            'id' => $loan->id,
            'loan_no' => $loan->loan_no,
            'company_id' => $loan->company_id,
            'status' => $loan->status,
            'principal' => (string) $loan->principal,
            'annual_interest_rate' => (string) $loan->annual_interest_rate,
            'interest_method' => $loan->interest_method,
            'repayment_frequency' => $loan->repayment_frequency,
            'tenor' => $loan->tenor,
            'processing_fee' => (string) $loan->processing_fee,
            'total_interest' => (string) $loan->total_interest,
            'total_repayable' => (string) $loan->total_repayable,
            'disbursement_date' => $loan->disbursement_date?->toDateString(),
            'maturity_date' => $loan->maturity_date?->toDateString(),
            'first_due_date' => $loan->first_due_date?->toDateString(),
            'approved_at' => $loan->approved_at?->toIso8601String(),
            'disbursed_at' => $loan->disbursed_at?->toIso8601String(),
            'closed_at' => $loan->closed_at?->toIso8601String(),
            'disbursement_journal_id' => $loan->disbursement_journal_id,
            'product' => $loan->product ? ['id' => $loan->product->id, 'code' => $loan->product->code, 'name' => $loan->product->name] : null,
            'customer' => $loan->customer ? ['id' => $loan->customer->id, 'customer_no' => $loan->customer->customer_no, 'display_name' => $loan->customer->display_name] : null,
            'outstanding' => (string) $loan->outstanding(),
            'schedules' => $schedules->map(fn ($s) => [
                'id' => $s->id,
                'installment_no' => $s->installment_no,
                'due_date' => $s->due_date->toDateString(),
                'principal_due' => (string) $s->principal_due,
                'interest_due' => (string) $s->interest_due,
                'fee_due' => (string) $s->fee_due,
                'penalty_due' => (string) $s->penalty_due,
                'total_due' => (string) $s->total_due,
                'principal_paid' => (string) $s->principal_paid,
                'interest_paid' => (string) $s->interest_paid,
                'fee_paid' => (string) $s->fee_paid,
                'penalty_paid' => (string) $s->penalty_paid,
                'remaining' => (string) $s->remaining(),
                'status' => $s->status,
            ])->values()->all(),
            'repayments' => ($loan->relationLoaded('repayments') ? $loan->repayments : collect())->map(fn ($r) => [
                'id' => $r->id,
                'amount' => (string) $r->amount,
                'payment_date' => $r->payment_date->toDateString(),
                'principal_component' => (string) $r->principal_component,
                'interest_component' => (string) $r->interest_component,
                'fee_component' => (string) $r->fee_component,
                'penalty_component' => (string) $r->penalty_component,
                'journal_transaction_id' => $r->journal_transaction_id,
            ])->values()->all(),
        ];
    }
}
