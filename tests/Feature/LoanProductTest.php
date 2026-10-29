<?php

use App\Models\Company;
use App\Models\GeneralLedger\Account;
use App\Models\GeneralLedger\Ledger;
use App\Models\Loan\LoanProduct;

function seedProductCompany(): array
{
    $company = Company::create(['name' => 'Loan Ops Company', 'code' => 'LOC']);
    $master = Ledger::create(['name' => 'Master Chart', 'code' => 'MC', 'company_id' => $company->id]);
    $customers = Ledger::create(['name' => 'Customers', 'code' => 'CUST', 'company_id' => $company->id]);

    $leaf = function (string $code, string $name, string $category, string $balance, ?string $parentCode = null) use ($master) {
        $parentId = $parentCode
            ? Account::where('ledger_id', $master->id)->where('code', $parentCode)->value('id')
            : null;

        return Account::create([
            'name' => $name,
            'code' => $code,
            'description' => $name,
            'ledger_id' => $master->id,
            'parent_id' => $parentId,
            'balance_type' => $balance,
            'category' => $category,
            'status' => 'active',
        ]);
    };

    $leaf('1001', 'Cash on Hand', 'CashAndCashEquivalents', 'debit');
    $leaf('1100', 'Customer Control', 'AccountsReceivable', 'debit');
    Account::where('ledger_id', $master->id)->where('code', '1100')->update(['subledger_id' => $customers->id]);
    $leaf('1110', 'Loan Principal Portfolio', 'AccountsReceivable', 'debit');
    $leaf('1120', 'Interest Receivable - Loans', 'AccountsReceivable', 'debit');
    $leaf('1130', 'Penalty Receivable - Loans', 'AccountsReceivable', 'debit');
    $leaf('4000', 'Operating Revenue', 'OperatingRevenue', 'credit');
    $leaf('4003', 'Loan Interest Income', 'OperatingRevenue', 'credit', '4000');
    $leaf('4004', 'Loan Fee Income', 'OperatingRevenue', 'credit', '4000');

    $accounts = Account::where('ledger_id', $master->id)->get()->keyBy('code');

    return compact('company', 'master', 'customers', 'accounts');
}

function productPayload(Company $company, $accounts): array
{
    return [
        'company_id' => (string) $company->id,
        'code' => 'SAL',
        'name' => 'Salary Loan',
        'interest_method' => 'flat',
        'annual_interest_rate' => 36,
        'repayment_frequency' => 'monthly',
        'min_principal' => 10000,
        'max_principal' => 1000000,
        'min_tenor' => 1,
        'max_tenor' => 12,
        'processing_fee_rate' => 2,
        'processing_fee_flat' => 0,
        'penalty_rate_daily' => 0.1,
        'penalty_grace_days' => 3,
        'mapping' => [
            'principal_receivable' => (string) $accounts['1110']->id,
            'interest_receivable' => (string) $accounts['1120']->id,
            'interest_income' => (string) $accounts['4003']->id,
            'fee_income' => (string) $accounts['4004']->id,
            'penalty_receivable' => (string) $accounts['1130']->id,
            'penalty_income' => (string) $accounts['4004']->id,
        ],
    ];
}

test('product store creates product with six mappings', function () {
    ['company' => $company, 'accounts' => $accounts] = seedProductCompany();

    $this->post(route('loans.products.store'), productPayload($company, $accounts))->assertRedirect();

    $product = LoanProduct::firstOrFail();
    expect($product->code)->toBe('SAL');
    expect($product->mappings()->count())->toBe(6);
});

test('product store creates product without mappings', function () {
    ['company' => $company, 'accounts' => $accounts] = seedProductCompany();
    $payload = productPayload($company, $accounts);
    unset($payload['mapping']);

    $this->post(route('loans.products.store'), $payload)->assertRedirect();

    $product = LoanProduct::firstOrFail();
    expect($product->code)->toBe('SAL');
    expect($product->mappings()->count())->toBe(0);
});

test('product update can complete mappings later', function () {
    ['company' => $company, 'accounts' => $accounts] = seedProductCompany();
    $payload = productPayload($company, $accounts);
    unset($payload['mapping']);

    $this->post(route('loans.products.store'), $payload)->assertRedirect();
    $product = LoanProduct::firstOrFail();

    $this->put(route('loans.products.update', ['product' => $product->id]), [
        'mapping' => productPayload($company, $accounts)['mapping'],
    ])->assertRedirect();

    expect($product->fresh()->mappings()->count())->toBe(6);
});

test('product store rejects incomplete mapping', function () {
    ['company' => $company, 'accounts' => $accounts] = seedProductCompany();
    $payload = productPayload($company, $accounts);
    unset($payload['mapping']['fee_income']);

    $this->post(route('loans.products.store'), $payload)->assertStatus(422);
    expect(LoanProduct::count())->toBe(0);
});

test('product store rejects wrong balance type mapping', function () {
    ['company' => $company, 'accounts' => $accounts] = seedProductCompany();
    $payload = productPayload($company, $accounts);
    $payload['mapping']['interest_income'] = (string) $accounts['1120']->id;

    $this->post(route('loans.products.store'), $payload)->assertSessionHasErrors(['account_id']);
    expect(LoanProduct::count())->toBe(0);
});

test('product mapping update swaps account', function () {
    ['company' => $company, 'accounts' => $accounts] = seedProductCompany();
    $this->post(route('loans.products.store'), productPayload($company, $accounts))->assertRedirect();
    $product = LoanProduct::firstOrFail();

    $alt = Account::create([
        'name' => 'Alt Fee Income',
        'code' => '4005',
        'description' => 'Alt',
        'ledger_id' => $accounts['4003']->ledger_id,
        'parent_id' => Account::where('code', '4000')->value('id'),
        'balance_type' => 'credit',
        'category' => 'OperatingRevenue',
        'status' => 'active',
    ]);

    $this->put(route('loans.products.mapping', ['product' => $product->id]), [
        'operation_type' => 'fee_income',
        'account_id' => (string) $alt->id,
    ])->assertRedirect();

    expect($product->fresh()->accountFor('fee_income')?->id)->toBe($alt->id);
});
