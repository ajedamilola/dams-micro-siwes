<?php

namespace App\Http\Controllers\Loan;

use App\Enums\GeneralLedger\AccountCategory;
use App\Enums\Loan\CustomerStatus;
use App\Enums\Loan\CustomerType;
use App\Enums\Loan\Gender;
use App\Enums\Loan\KycStatus;
use App\Enums\Loan\KycTier;
use App\Enums\Loan\MaritalStatus;
use App\Enums\Loan\MeansOfIdType;
use App\Enums\Loan\ProofOfAddressType;
use App\Enums\Loan\RiskRating;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\GeneralLedger\Account;
use App\Models\GeneralLedger\JournalEntry;
use App\Models\GeneralLedger\Ledger;
use App\Models\Loan\LoanCustomer;
use App\Support\BusinessDate;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class LoanCustomerController extends Controller
{
    /**
     * Display a listing of loan customers.
     */
    public function index()
    {
        $rows = DB::select(
            'select lc.*, c.name as company_name, a.code as account_code, a.status as account_status,
                (select je.balance_after from journal_entries je
                    inner join journal_transactions jt on jt.id = je.journal_transaction_id
                    where je.account_id = a.id and jt.status = ?
                    order by je.id desc limit 1) as account_balance
            from loan_customers lc
            left join companies c on c.id = lc.company_id
            left join accounts a on a.id = lc.account_id
            where lc.deleted_at is null
            order by lc.customer_no asc',
            ['POSTED']
        );

        $customers = array_map(fn (object $row) => self::presentRow((array) $row), $rows);

        return Inertia::render('Loan/Customers/Index', [
            'customers' => $customers,
            'companies' => Company::orderBy('name')->get(['id', 'name', 'code']),
            'kycTiers' => collect(KycTier::cases())->map(fn (KycTier $tier) => [
                'value' => $tier->value,
                ...$tier->requirements(),
            ])->values()->all(),
        ]);
    }

    /**
     * Store a newly created loan customer, auto-creating or linking the
     * customer GL account in the Customers subledger.
     */
    public function store(Request $request)
    {
        $data = $this->validateCustomer($request);

        // CBN Dec 2023 amendment: Tier1 needs BVN or NIN, Tier2 and Tier3
        // need both. Tier3 additionally needs a valid means of ID and proof
        // of address.
        $this->assertKycIdentifiers($data);

        $company = Company::findOrFail($data['company_id']);

        $customer = DB::transaction(function () use ($data, $company) {
            $customerNo = $this->nextCustomerNo((int) $company->id);

            $glMode = $data['gl_mode'] ?? 'auto';
            $account = null;

            if ($glMode === 'link') {
                $account = Account::findOrFail($data['link_account_id']);
                $this->assertLinkableAccount($account, (int) $company->id);

                if (LoanCustomer::where('account_id', $account->id)->exists()) {
                    throw ValidationException::withMessages([
                        'link_account_id' => 'This GL account is already linked to another customer.',
                    ]);
                }
            }

            if ($glMode === 'auto') {
                $customersLedger = Ledger::where('company_id', $company->id)->where('code', 'CUST')->first();
                if ($customersLedger === null) {
                    $customersLedger = Ledger::create([
                        'name' => 'Customers',
                        'code' => 'CUST',
                        'company_id' => $company->id,
                    ]);
                }

                if (Account::where('ledger_id', $customersLedger->id)->where('code', $customerNo)->exists()) {
                    throw ValidationException::withMessages([
                        'customer_no' => 'A GL account already uses this customer number.',
                    ]);
                }

                $account = Account::create([
                    'name' => $this->displayName($data),
                    'code' => $customerNo,
                    'description' => "Loan customer account for {$this->displayName($data)}.",
                    'ledger_id' => $customersLedger->id,
                    'balance_type' => 'debit',
                    'category' => AccountCategory::AccountsReceivable->value,
                    'status' => 'active',
                ]);
            }

            return LoanCustomer::create([
                ...collect($data)->except(['gl_mode', 'link_account_id'])->all(),
                'customer_no' => $customerNo,
                'account_id' => $account?->id,
            ]);
        });

        return redirect()->route('loans.customers.show', ['customer' => $customer->id]);
    }

    /**
     * Display the specified loan customer with the linked GL account.
     */
    public function show(string $customer)
    {
        $customer = LoanCustomer::with(['account', 'company', 'documents'])->findOrFail($customer);

        $account = $customer->account;
        $recentEntries = [];
        if ($account) {
            $recentEntries = JournalEntry::with('transaction')
                ->where('account_id', $account->id)
                ->whereHas('transaction', fn ($query) => $query->where('status', 'POSTED'))
                ->orderByDesc('id')
                ->limit(10)
                ->get()
                ->map(fn (JournalEntry $entry) => [
                    'id' => $entry->id,
                    'type' => $entry->type,
                    'value' => (string) $entry->value,
                    'balance_after' => (string) $entry->balance_after,
                    'ref' => $entry->transaction?->ref,
                    'description' => $entry->transaction?->description,
                    'effective_date' => $entry->transaction?->effective_date,
                ])->all();
        }

        $linkableAccounts = $this->linkableAccounts((int) $customer->company_id, $customer->account_id);

        return Inertia::render('Loan/Customers/Show', [
            'customer' => self::present($customer),
            'recentEntries' => $recentEntries,
            'linkableAccounts' => $linkableAccounts,
        ]);
    }

    /**
     * Update the specified loan customer and keep the GL account in sync.
     */
    public function update(Request $request, string $customer)
    {
        $customer = LoanCustomer::findOrFail($customer);

        $data = $this->validateCustomer($request, $customer->id);

        $this->assertKycIdentifiers($data);

        DB::transaction(function () use ($data, $customer) {
            $updates = collect($data)->except(['gl_mode', 'link_account_id', 'company_id'])->all();

            if ($updates !== []) {
                $assignments = implode(', ', array_map(fn (string $column) => "{$column} = ?", array_keys($updates)));
                $bindings = array_values($updates);
                $bindings[] = now();
                $bindings[] = $customer->id;

                DB::update(
                    "update loan_customers set {$assignments}, updated_at = ? where id = ? and deleted_at is null",
                    $bindings
                );

                $customer->refresh();
            }

            // Keep the auto-created GL account name in step with the customer.
            $account = $customer->account;
            if ($account && $account->code === $customer->customer_no) {
                DB::update(
                    'update accounts set name = ?, updated_at = ? where id = ?',
                    [$customer->display_name, now(), $account->id]
                );
            }

            // Allow linking a GL account after the fact when none is set.
            if (! $customer->account_id && ! empty($data['link_account_id'])) {
                $account = Account::findOrFail($data['link_account_id']);
                $this->assertLinkableAccount($account, (int) $customer->company_id);

                if (LoanCustomer::where('account_id', $account->id)->where('id', '!=', $customer->id)->exists()) {
                    throw ValidationException::withMessages([
                        'link_account_id' => 'This GL account is already linked to another customer.',
                    ]);
                }

                DB::update(
                    'update loan_customers set account_id = ?, updated_at = ? where id = ?',
                    [$account->id, now(), $customer->id]
                );
            }
        });

        return back();
    }

    /**
     * Remove the customer. Blocked when the linked GL account has postings.
     */
    public function destroy(string $customer)
    {
        $customer = LoanCustomer::findOrFail($customer);

        if ($customer->account_id && JournalEntry::where('account_id', $customer->account_id)->exists()) {
            return back()->withErrors(['customer' => 'This customer has GL postings and cannot be deleted. Blacklist the customer instead.']);
        }

        DB::transaction(function () use ($customer) {
            $account = $customer->account;
            $now = now();

            DB::update(
                'update loan_customers set deleted_at = ?, updated_at = ? where id = ? and deleted_at is null',
                [$now, $now, $customer->id]
            );

            // Remove the auto-created GL account when it has no postings.
            if ($account && $account->code === $customer->customer_no) {
                DB::delete('delete from accounts where id = ?', [$account->id]);
            }
        });

        return redirect()->route('loans.customers.index');
    }

    /**
     * Mark KYC as verified. Only pending or in-review customers qualify.
     */
    public function verify(string $customer)
    {
        $customer = LoanCustomer::findOrFail($customer);

        if (! in_array($customer->kyc_status, [KycStatus::Pending->value, KycStatus::InReview->value], true)) {
            return back()->withErrors(['kyc_status' => 'Only pending or in-review customers can be verified.']);
        }

        $this->assertKycIdentifiers($customer->toArray(), true);

        $customer->update(['kyc_status' => KycStatus::Verified->value]);

        return back();
    }

    /**
     * Send KYC back to pending review or mark it rejected.
     */
    public function markReview(Request $request, string $customer)
    {
        $customer = LoanCustomer::findOrFail($customer);

        $data = $request->validate([
            'kyc_status' => ['required', Rule::in([KycStatus::InReview->value, KycStatus::Rejected->value, KycStatus::Pending->value])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $customer->update($data);

        return back();
    }

    /**
     * Keyword search for linking a customer to a future loan.
     */
    public function search(Request $request)
    {
        $data = $request->validate([
            'q' => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);

        $tokens = preg_split('/\s+/', strtolower(trim((string) ($data['q'] ?? ''))), -1, PREG_SPLIT_NO_EMPTY);

        $sql = 'select lc.id, lc.customer_no, lc.customer_type, lc.first_name, lc.middle_name,
                lc.last_name, lc.business_name, lc.phone, lc.kyc_status, lc.account_id
            from loan_customers lc
            where lc.deleted_at is null';
        $bindings = [];

        foreach ($tokens as $token) {
            $sql .= ' and (lower(lc.customer_no) like ? or lower(lc.first_name) like ?
                or lower(lc.last_name) like ? or lower(lc.phone) like ?
                or lower(coalesce(lc.business_name, \'\')) like ?)';
            $like = "%{$token}%";
            array_push($bindings, $like, $like, $like, $like, $like);
        }

        $sql .= ' order by lc.customer_no asc limit 10';

        $customers = array_map(fn (object $row) => [
            'id' => $row->id,
            'customer_no' => $row->customer_no,
            'display_name' => self::displayNameFromRow((array) $row),
            'phone' => $row->phone,
            'kyc_status' => $row->kyc_status,
            'account_id' => $row->account_id,
        ], DB::select($sql, $bindings));

        return response()->json(['data' => $customers]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateCustomer(Request $request, ?string $ignoreId = null): array
    {
        $companyId = $request->input('company_id');

        return $request->validate([
            'company_id' => $ignoreId ? ['sometimes', 'string', 'exists:companies,id'] : ['required', 'string', 'exists:companies,id'],
            'customer_type' => ['required', Rule::enum(CustomerType::class)],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'business_name' => ['nullable', 'string', 'max:255', Rule::requiredIf(fn () => $request->input('customer_type') === 'corporate')],
            'rc_number' => ['nullable', 'string', 'max:50', Rule::requiredIf(fn () => $request->input('customer_type') === 'corporate')],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:'.BusinessDate::current()->subYears(18)->toDateString()],
            'marital_status' => ['nullable', Rule::enum(MaritalStatus::class)],
            'phone' => [
                'required',
                'string',
                'regex:/^(0[789][01]\d{8}|\+234[789][01]\d{8})$/',
                $ignoreId
                    ? Rule::unique('loan_customers', 'phone')->where('company_id', $request->input('company_id'))->ignore($ignoreId)
                    : Rule::unique('loan_customers', 'phone')->where('company_id', $companyId),
            ],
            'phone_alt' => ['nullable', 'string', 'regex:/^(0[789][01]\d{8}|\+234[789][01]\d{8})$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['required', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'lga' => ['nullable', 'string', 'max:100'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'bvn' => [
                'nullable', 'string', 'regex:/^\d{11}$/',
                $ignoreId
                    ? Rule::unique('loan_customers', 'bvn')->ignore($ignoreId)->whereNull('deleted_at')
                    : Rule::unique('loan_customers', 'bvn')->whereNull('deleted_at'),
            ],
            'nin' => [
                'nullable', 'string', 'regex:/^\d{11}$/',
                $ignoreId
                    ? Rule::unique('loan_customers', 'nin')->ignore($ignoreId)->whereNull('deleted_at')
                    : Rule::unique('loan_customers', 'nin')->whereNull('deleted_at'),
            ],
            'means_of_id_type' => ['nullable', Rule::enum(MeansOfIdType::class)],
            'means_of_id_number' => ['nullable', 'string', 'max:100'],
            'id_expiry_date' => ['nullable', 'date', 'after:'.BusinessDate::today()],
            'proof_of_address_type' => ['nullable', Rule::enum(ProofOfAddressType::class)],
            'kyc_tier' => ['required', Rule::enum(KycTier::class)],
            'kyc_status' => ['sometimes', Rule::enum(KycStatus::class)],
            'risk_rating' => ['sometimes', Rule::enum(RiskRating::class)],
            'is_pep' => ['sometimes', 'boolean'],
            'employment_status' => ['nullable', 'string', 'max:100'],
            'employer_name' => ['nullable', 'string', 'max:255'],
            'monthly_income' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'next_of_kin_name' => ['nullable', 'string', 'max:255'],
            'next_of_kin_relationship' => ['nullable', 'string', 'max:100'],
            'next_of_kin_phone' => ['nullable', 'string', 'regex:/^(0[789][01]\d{8}|\+234[789][01]\d{8})$/'],
            'status' => ['sometimes', Rule::enum(CustomerStatus::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
            'gl_mode' => ['sometimes', Rule::in(['auto', 'link', 'none'])],
            'link_account_id' => ['nullable', 'string', 'exists:accounts,id', Rule::requiredIf(fn () => $request->input('gl_mode') === 'link')],
        ]);
    }

    /**
     * Enforce the CBN identifier rules for each tier.
     *
     * @param  array<string, mixed>  $data
     */
    private function assertKycIdentifiers(array $data, bool $isVerify = false): void
    {
        $tier = $data['kyc_tier'] ?? KycTier::Tier3->value;
        $bvn = $data['bvn'] ?? null;
        $nin = $data['nin'] ?? null;

        if ($tier === KycTier::Tier1->value && ! $bvn && ! $nin) {
            throw ValidationException::withMessages([
                'bvn' => 'Tier 1 customers need a BVN or a NIN (CBN Dec 2023 amendment).',
            ]);
        }

        if (in_array($tier, [KycTier::Tier2->value, KycTier::Tier3->value], true)) {
            if (! $bvn || ! $nin) {
                throw ValidationException::withMessages([
                    'bvn' => 'Tier 2 and Tier 3 customers need both a BVN and a NIN.',
                    'nin' => 'Tier 2 and Tier 3 customers need both a BVN and a NIN.',
                ]);
            }

            if ($tier === KycTier::Tier3->value) {
                if (empty($data['means_of_id_type']) || empty($data['means_of_id_number'])) {
                    throw ValidationException::withMessages([
                        'means_of_id_type' => 'Tier 3 (full KYC) needs a valid means of identification and its number.',
                    ]);
                }

                if (empty($data['proof_of_address_type'])) {
                    throw ValidationException::withMessages([
                        'proof_of_address_type' => 'Tier 3 (full KYC) needs a verified proof of address.',
                    ]);
                }
            }
        }

        if ($isVerify && ($data['kyc_tier'] ?? null) === KycTier::Tier1->value) {
            // Loan disbursement still expects full KYC; keep the guard explicit.
            throw ValidationException::withMessages([
                'kyc_tier' => 'Loan customers need Tier 3 full KYC before verification.',
            ]);
        }
    }

    private function displayName(array $data): string
    {
        if (($data['customer_type'] ?? 'individual') === 'corporate') {
            return trim((string) ($data['business_name'] ?? "{$data['first_name']} {$data['last_name']}"));
        }

        return trim("{$data['first_name']} ".($data['middle_name'] ? "{$data['middle_name']} " : '')."{$data['last_name']}");
    }

    private function nextCustomerNo(int $companyId): string
    {
        $year = BusinessDate::current()->format('Y');
        $prefix = "LC-{$year}-";

        $max = LoanCustomer::where('company_id', $companyId)
            ->where('customer_no', 'like', "{$prefix}%")
            ->orderByDesc('customer_no')
            ->value('customer_no');

        $next = 1;
        if ($max && preg_match('/(\d+)$/', $max, $matches)) {
            $next = ((int) $matches[1]) + 1;
        }

        return $prefix.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    private function assertLinkableAccount(Account $account, int $companyId): void
    {
        $ledger = Ledger::find($account->ledger_id);

        if (! $ledger || (int) $ledger->company_id !== $companyId || $ledger->code !== 'CUST') {
            throw ValidationException::withMessages([
                'link_account_id' => 'Only accounts in the Customers subledger can be linked.',
            ]);
        }

        if ($account->subledger_id !== null) {
            throw ValidationException::withMessages([
                'link_account_id' => 'Control accounts cannot be linked to a customer.',
            ]);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function linkableAccounts(int $companyId, ?string $exceptAccountId = null): array
    {
        $ledger = Ledger::where('company_id', $companyId)->where('code', 'CUST')->first();

        if (! $ledger) {
            return [];
        }

        $linkedIds = LoanCustomer::whereNotNull('account_id')->pluck('account_id')->all();
        $linkedIds = array_filter($linkedIds, fn ($id) => $id !== $exceptAccountId);

        return Account::where('ledger_id', $ledger->id)
            ->where('status', 'active')
            ->whereNull('subledger_id')
            ->whereNotIn('id', $linkedIds)
            ->orderBy('code')
            ->limit(50)
            ->get(['id', 'code', 'name'])
            ->map(fn (Account $account) => [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
            ])->values()->all();
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private static function presentRow(array $row): array
    {
        $bvn = $row['bvn'] ?? null;
        $nin = $row['nin'] ?? null;

        return [
            'id' => $row['id'],
            'customer_no' => $row['customer_no'],
            'company_id' => $row['company_id'],
            'company_name' => $row['company_name'] ?? null,
            'account_id' => $row['account_id'] ?? null,
            'account_code' => $row['account_code'] ?? null,
            'account_balance' => (string) ($row['account_balance'] ?? '0'),
            'account_status' => $row['account_status'] ?? null,
            'customer_type' => $row['customer_type'],
            'first_name' => $row['first_name'],
            'middle_name' => $row['middle_name'] ?? null,
            'last_name' => $row['last_name'],
            'display_name' => self::displayNameFromRow($row),
            'business_name' => $row['business_name'] ?? null,
            'rc_number' => $row['rc_number'] ?? null,
            'gender' => $row['gender'] ?? null,
            'date_of_birth' => self::dateOnly($row['date_of_birth'] ?? null),
            'marital_status' => $row['marital_status'] ?? null,
            'phone' => $row['phone'],
            'phone_alt' => $row['phone_alt'] ?? null,
            'email' => $row['email'] ?? null,
            'address' => $row['address'],
            'city' => $row['city'] ?? null,
            'state' => $row['state'] ?? null,
            'lga' => $row['lga'] ?? null,
            'nationality' => $row['nationality'] ?? null,
            'bvn_masked' => $bvn ? '******'.substr((string) $bvn, -4) : null,
            'nin_masked' => $nin ? '******'.substr((string) $nin, -4) : null,
            'bvn' => $bvn,
            'nin' => $nin,
            'means_of_id_type' => $row['means_of_id_type'] ?? null,
            'means_of_id_number' => $row['means_of_id_number'] ?? null,
            'id_expiry_date' => self::dateOnly($row['id_expiry_date'] ?? null),
            'proof_of_address_type' => $row['proof_of_address_type'] ?? null,
            'kyc_tier' => $row['kyc_tier'],
            'kyc_status' => $row['kyc_status'],
            'risk_rating' => $row['risk_rating'] ?? null,
            'is_pep' => (bool) ($row['is_pep'] ?? false),
            'employment_status' => $row['employment_status'] ?? null,
            'employer_name' => $row['employer_name'] ?? null,
            'monthly_income' => isset($row['monthly_income']) ? (string) $row['monthly_income'] : null,
            'next_of_kin_name' => $row['next_of_kin_name'] ?? null,
            'next_of_kin_relationship' => $row['next_of_kin_relationship'] ?? null,
            'next_of_kin_phone' => $row['next_of_kin_phone'] ?? null,
            'status' => $row['status'],
            'notes' => $row['notes'] ?? null,
            'created_at' => self::isoDateTime($row['created_at'] ?? null),
            'updated_at' => self::isoDateTime($row['updated_at'] ?? null),
        ];
    }

    /** @param array<string, mixed> $row */
    private static function displayNameFromRow(array $row): string
    {
        if (($row['customer_type'] ?? 'individual') === 'corporate') {
            return (string) ($row['business_name'] ?: trim("{$row['first_name']} {$row['last_name']}"));
        }

        return trim("{$row['first_name']} {$row['middle_name']} {$row['last_name']}");
    }

    private static function dateOnly(mixed $value): ?string
    {
        return $value ? Carbon::parse($value)->toDateString() : null;
    }

    private static function isoDateTime(mixed $value): ?string
    {
        return $value ? Carbon::parse($value)->toIso8601String() : null;
    }

    /**
     * @param  Collection<int, LoanCustomer>  $customers
     * @return array<int, array<string, mixed>>
     */
    private static function presentMany($customers): array
    {
        return $customers->map(fn (LoanCustomer $customer) => self::present($customer))->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    private static function present(LoanCustomer $customer): array
    {
        $account = $customer->relationLoaded('account') ? $customer->account : $customer->account()->first();

        return [
            'id' => $customer->id,
            'customer_no' => $customer->customer_no,
            'company_id' => $customer->company_id,
            'company_name' => $customer->company?->name,
            'account_id' => $customer->account_id,
            'account_code' => $account?->code,
            'account_balance' => $account ? (string) $account->account_balance : '0',
            'account_status' => $account?->status,
            'customer_type' => $customer->customer_type,
            'first_name' => $customer->first_name,
            'middle_name' => $customer->middle_name,
            'last_name' => $customer->last_name,
            'display_name' => $customer->display_name,
            'business_name' => $customer->business_name,
            'rc_number' => $customer->rc_number,
            'gender' => $customer->gender,
            'date_of_birth' => $customer->date_of_birth?->toDateString(),
            'marital_status' => $customer->marital_status,
            'phone' => $customer->phone,
            'phone_alt' => $customer->phone_alt,
            'email' => $customer->email,
            'address' => $customer->address,
            'city' => $customer->city,
            'state' => $customer->state,
            'lga' => $customer->lga,
            'nationality' => $customer->nationality,
            'bvn_masked' => $customer->bvn ? '******'.substr($customer->bvn, -4) : null,
            'nin_masked' => $customer->nin ? '******'.substr($customer->nin, -4) : null,
            'bvn' => $customer->bvn,
            'nin' => $customer->nin,
            'means_of_id_type' => $customer->means_of_id_type,
            'means_of_id_number' => $customer->means_of_id_number,
            'id_expiry_date' => $customer->id_expiry_date?->toDateString(),
            'proof_of_address_type' => $customer->proof_of_address_type,
            'kyc_tier' => $customer->kyc_tier,
            'kyc_status' => $customer->kyc_status,
            'risk_rating' => $customer->risk_rating,
            'is_pep' => (bool) $customer->is_pep,
            'employment_status' => $customer->employment_status,
            'employer_name' => $customer->employer_name,
            'monthly_income' => $customer->monthly_income !== null ? (string) $customer->monthly_income : null,
            'next_of_kin_name' => $customer->next_of_kin_name,
            'next_of_kin_relationship' => $customer->next_of_kin_relationship,
            'next_of_kin_phone' => $customer->next_of_kin_phone,
            'status' => $customer->status,
            'notes' => $customer->notes,
            'created_at' => $customer->created_at?->toIso8601String(),
            'updated_at' => $customer->updated_at?->toIso8601String(),
        ];
    }
}
