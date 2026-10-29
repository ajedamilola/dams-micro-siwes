<?php

namespace App\Http\Controllers\Loan;

use App\Enums\Loan\InterestMethod;
use App\Enums\Loan\LoanProductStatus;
use App\Enums\Loan\OperationType;
use App\Enums\Loan\RepaymentFrequency;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\GeneralLedger\Account;
use App\Models\Loan\LoanProduct;
use App\Services\Loan\LoanPosting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class LoanProductController extends Controller
{
    public function index()
    {
        $products = LoanProduct::with(['company', 'mappings.account'])->orderBy('code')->get();

        return Inertia::render('Loan/Products/Index', [
            'products' => $products->map(fn (LoanProduct $p) => self::present($p))->values()->all(),
            'companies' => Company::orderBy('name')->get(['id', 'name', 'code']),
        ]);
    }

    public function store(Request $request, LoanPosting $posting)
    {
        $data = $request->validate([
            'company_id' => ['required', 'string', 'exists:companies,id'],
            'code' => ['required', 'string', 'max:20'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'interest_method' => ['required', Rule::enum(InterestMethod::class)],
            'annual_interest_rate' => ['required', 'numeric', 'min:0', 'max:500'],
            'repayment_frequency' => ['required', Rule::enum(RepaymentFrequency::class)],
            'min_principal' => ['required', 'numeric', 'min:0'],
            'max_principal' => ['required', 'numeric', 'min:0', 'gte:min_principal'],
            'min_tenor' => ['required', 'integer', 'min:1', 'max:600'],
            'max_tenor' => ['required', 'integer', 'min:1', 'max:600', 'gte:min_tenor'],
            'processing_fee_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'penalty_rate_daily' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'penalty_grace_days' => ['nullable', 'integer', 'min:0', 'max:90'],
            'status' => ['sometimes', Rule::enum(LoanProductStatus::class)],
            'mapping' => ['sometimes', 'array:'.collect(OperationType::cases())->pluck('value')->implode(',')],
            'mapping.*' => ['nullable', 'string', 'exists:accounts,id'],
        ]);

        if (LoanProduct::where('company_id', $data['company_id'])->where('code', $data['code'])->exists()) {
            return back()->withErrors(['code' => 'This product code is already in use for this company.']);
        }

        $mapping = array_filter($data['mapping'] ?? []);

        if ($mapping !== []) {
            $this->assertMappingComplete(array_keys($mapping));
        }

        $product = DB::transaction(function () use ($data, $mapping, $posting) {
            $product = LoanProduct::create([
                ...collect($data)->except(['mapping'])->all(),
                'processing_fee_rate' => $data['processing_fee_rate'] ?? 0,
                'penalty_rate_daily' => $data['penalty_rate_daily'] ?? 0,
                'penalty_grace_days' => $data['penalty_grace_days'] ?? 0,
                'status' => $data['status'] ?? LoanProductStatus::Active->value,
            ]);

            foreach ($mapping as $operation => $accountId) {
                $type = OperationType::from($operation);
                $account = Account::with('ledger')->findOrFail($accountId);
                $this->assertSameCompany($account, (string) $product->company_id);
                $posting->assertMapping($account, $type);
                $product->mappings()->create(['operation_type' => $type->value, 'account_id' => $account->id]);
            }

            return $product;
        });

        return redirect()->route('loans.products.show', ['product' => $product->id]);
    }

    public function show(string $product)
    {
        $product = LoanProduct::with(['company', 'mappings.account', 'loans.customer' => fn ($q) => $q->withTrashed()->latest()->limit(20)])->findOrFail($product);

        return Inertia::render('Loan/Products/Show', [
            'product' => self::present($product),
            'operationTypes' => collect(OperationType::cases())->map(fn ($c) => ['value' => $c->value])->values()->all(),
        ]);
    }

    public function update(Request $request, string $product, LoanPosting $posting)
    {
        $product = LoanProduct::findOrFail($product);

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'annual_interest_rate' => ['sometimes', 'required', 'numeric', 'min:0', 'max:500'],
            'min_principal' => ['sometimes', 'required', 'numeric', 'min:0'],
            'max_principal' => ['sometimes', 'required', 'numeric', 'min:0'],
            'min_tenor' => ['sometimes', 'required', 'integer', 'min:1', 'max:600'],
            'max_tenor' => ['sometimes', 'required', 'integer', 'min:1', 'max:600'],
            'processing_fee_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'penalty_rate_daily' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'penalty_grace_days' => ['nullable', 'integer', 'min:0', 'max:90'],
            'status' => ['sometimes', Rule::enum(LoanProductStatus::class)],
            'mapping' => ['sometimes', 'array:'.collect(OperationType::cases())->pluck('value')->implode(',')],
            'mapping.*' => ['nullable', 'string', 'exists:accounts,id'],
        ]);

        $mapping = array_filter($data['mapping'] ?? []);

        if ($mapping !== []) {
            $this->assertMappingComplete(array_keys($mapping));
        }

        DB::transaction(function () use ($product, $data, $mapping, $posting) {
            $product->update(collect($data)->except('mapping')->all());

            foreach ($mapping as $operation => $accountId) {
                $type = OperationType::from($operation);
                $account = Account::with('ledger')->findOrFail($accountId);
                $this->assertSameCompany($account, (string) $product->company_id);
                $posting->assertMapping($account, $type);
                $product->mappings()->updateOrCreate(
                    ['operation_type' => $type->value],
                    ['account_id' => $account->id]
                );
            }
        });

        return back();
    }

    public function updateMapping(Request $request, string $product, LoanPosting $posting)
    {
        $product = LoanProduct::findOrFail($product);

        $data = $request->validate([
            'operation_type' => ['required', Rule::enum(OperationType::class)],
            'account_id' => ['required', 'string', 'exists:accounts,id'],
        ]);

        $type = OperationType::from($data['operation_type']);
        $account = Account::with('ledger')->findOrFail($data['account_id']);
        $this->assertSameCompany($account, (string) $product->company_id);
        $posting->assertMapping($account, $type);

        $product->mappings()->updateOrCreate(
            ['operation_type' => $type->value],
            ['account_id' => $account->id]
        );

        return back();
    }

    public function destroy(string $product)
    {
        $product = LoanProduct::findOrFail($product);

        if ($product->loans()->exists()) {
            return back()->withErrors(['product' => 'This product has loans and cannot be deleted. Deactivate it instead.']);
        }

        DB::transaction(function () use ($product) {
            $product->mappings()->delete();
            $product->delete();
        });

        return redirect()->route('loans.products.index');
    }

    private function assertMappingComplete(array $keys): void
    {
        $required = collect(OperationType::cases())->map(fn ($c) => $c->value)->all();
        sort($keys);
        sort($required);
        if ($keys !== $required) {
            abort(422, 'All six operation mappings are required when any mapping is set: '.implode(', ', $required).'.');
        }
    }

    private function assertSameCompany(Account $account, string $companyId): void
    {
        if ((string) $account->ledger?->company_id !== $companyId) {
            abort(422, "Account {$account->code} does not belong to this product company.");
        }
        if ($account->ledger?->code === 'CUST') {
            abort(422, "Account {$account->code} is a customer subledger account. Map a Master Chart leaf instead.");
        }
    }

    /** @return array<string, mixed> */
    private static function present(LoanProduct $product): array
    {
        return [
            'id' => $product->id,
            'company_id' => $product->company_id,
            'company_name' => $product->company?->name,
            'code' => $product->code,
            'name' => $product->name,
            'description' => $product->description,
            'interest_method' => $product->interest_method,
            'annual_interest_rate' => (string) $product->annual_interest_rate,
            'repayment_frequency' => $product->repayment_frequency,
            'min_principal' => (string) $product->min_principal,
            'max_principal' => (string) $product->max_principal,
            'min_tenor' => $product->min_tenor,
            'max_tenor' => $product->max_tenor,
            'processing_fee_rate' => (string) $product->processing_fee_rate,
            'penalty_rate_daily' => (string) $product->penalty_rate_daily,
            'penalty_grace_days' => $product->penalty_grace_days,
            'status' => $product->status,
            'mapping' => $product->relationLoaded('mappings')
                ? $product->mappings->mapWithKeys(fn ($m) => [$m->operation_type => [
                    'account_id' => $m->account_id,
                    'code' => $m->account?->code,
                    'name' => $m->account?->name,
                ]])->all()
                : [],
            'loans_count' => $product->relationLoaded('loans') ? $product->loans->count() : $product->loans()->count(),
            'recent_loans' => $product->relationLoaded('loans')
                ? $product->loans->map(fn ($l) => [
                    'id' => $l->id,
                    'loan_no' => $l->loan_no,
                    'status' => $l->status,
                    'principal' => (string) $l->principal,
                    'total_repayable' => (string) $l->total_repayable,
                    'customer_name' => $l->customer?->display_name,
                ])->values()->all()
                : [],
        ];
    }
}
