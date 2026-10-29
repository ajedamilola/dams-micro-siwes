<?php

use App\Models\Company;
use App\Models\GeneralLedger\Account;
use App\Models\GeneralLedger\JournalEntry;
use App\Models\GeneralLedger\Ledger;
use App\Models\Loan\LoanCustomer;

function seedLoanCompany(): array
{
    $company = Company::create(['name' => 'Loan Test Company', 'code' => 'LTC']);
    $master = Ledger::create(['name' => 'Master Chart', 'code' => 'MC', 'company_id' => $company->id]);
    $customers = Ledger::create(['name' => 'Customers', 'code' => 'CUST', 'company_id' => $company->id]);

    Account::create([
        'name' => 'Customer Control',
        'code' => '1100',
        'description' => 'Control account.',
        'ledger_id' => $master->id,
        'subledger_id' => $customers->id,
        'balance_type' => 'debit',
        'category' => 'AccountsReceivable',
        'status' => 'active',
    ]);

    return compact('company', 'master', 'customers');
}

function validCustomerPayload(Company $company): array
{
    return [
        'company_id' => (string) $company->id,
        'customer_type' => 'individual',
        'first_name' => 'Adaeze',
        'middle_name' => 'Ngozi',
        'last_name' => 'Okafor',
        'gender' => 'female',
        'date_of_birth' => '1990-04-12',
        'marital_status' => 'married',
        'phone' => '08034567891',
        'address' => '14 Allen Avenue, Ikeja',
        'city' => 'Lagos',
        'state' => 'Lagos',
        'lga' => 'Ikeja',
        'bvn' => '22145678901',
        'nin' => '90123456781',
        'means_of_id_type' => 'drivers_license',
        'means_of_id_number' => 'ABC12345AA',
        'id_expiry_date' => now()->addYear()->toDateString(),
        'proof_of_address_type' => 'utility_bill',
        'kyc_tier' => 'tier3',
        'employment_status' => 'self_employed',
        'employer_name' => 'Adaeze Fashion Hub',
        'monthly_income' => 450000,
        'next_of_kin_name' => 'Chukwuemeka Okafor',
        'next_of_kin_relationship' => 'Spouse',
        'next_of_kin_phone' => '08034567892',
        'status' => 'active',
        'gl_mode' => 'auto',
    ];
}

test('loan customers index renders customers with gl accounts', function () {
    ['company' => $company] = seedLoanCompany();

    $this->post(route('loans.customers.store'), validCustomerPayload($company))->assertRedirect();

    $response = $this->get(route('loans.customers.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Loan/Customers/Index')
        ->has('customers', 1)
        ->where('customers.0.customer_no', fn ($value) => str_starts_with($value, 'LC-'))
        ->where('customers.0.display_name', 'Adaeze Ngozi Okafor')
        ->where('customers.0.kyc_tier', 'tier3')
        ->has('kycTiers', 3)
    );
});

test('store creates a customer and auto-creates the gl account', function () {
    ['company' => $company, 'customers' => $customersLedger] = seedLoanCompany();

    $this->post(route('loans.customers.store'), validCustomerPayload($company))->assertRedirect();

    $customer = LoanCustomer::firstOrFail();
    expect($customer->customer_no)->toStartWith('LC-');
    expect($customer->account_id)->not->toBeNull();

    $account = Account::find($customer->account_id);
    expect($account)->not->toBeNull();
    expect($account->ledger_id)->toBe($customersLedger->id);
    expect($account->code)->toBe($customer->customer_no);
    expect($account->name)->toBe('Adaeze Ngozi Okafor');
    expect($account->category)->toBe('AccountsReceivable');
});

test('store rejects an invalid bvn', function () {
    ['company' => $company] = seedLoanCompany();

    $payload = validCustomerPayload($company);
    $payload['bvn'] = '12345';

    $this->post(route('loans.customers.store'), $payload)->assertInvalid(['bvn']);
    expect(LoanCustomer::count())->toBe(0);
});

test('store rejects tier3 without a means of id', function () {
    ['company' => $company] = seedLoanCompany();

    $payload = validCustomerPayload($company);
    $payload['means_of_id_type'] = null;
    $payload['means_of_id_number'] = null;

    $this->post(route('loans.customers.store'), $payload)->assertInvalid(['means_of_id_type']);
});

test('store rejects tier2 without both bvn and nin', function () {
    ['company' => $company] = seedLoanCompany();

    $payload = validCustomerPayload($company);
    $payload['kyc_tier'] = 'tier2';
    $payload['nin'] = null;
    $payload['means_of_id_type'] = null;
    $payload['means_of_id_number'] = null;
    $payload['proof_of_address_type'] = null;

    $this->post(route('loans.customers.store'), $payload)->assertInvalid(['nin']);
});

test('store rejects tier1 without bvn or nin', function () {
    ['company' => $company] = seedLoanCompany();

    $payload = validCustomerPayload($company);
    $payload['kyc_tier'] = 'tier1';
    $payload['bvn'] = null;
    $payload['nin'] = null;
    $payload['means_of_id_type'] = null;
    $payload['means_of_id_number'] = null;
    $payload['proof_of_address_type'] = null;

    $this->post(route('loans.customers.store'), $payload)->assertInvalid(['bvn']);
});

test('verify marks a tier3 customer verified', function () {
    ['company' => $company] = seedLoanCompany();

    $this->post(route('loans.customers.store'), validCustomerPayload($company))->assertRedirect();
    $customer = LoanCustomer::firstOrFail();

    $this->post(route('loans.customers.verify', ['customer' => $customer->id]))->assertRedirect();
    expect($customer->fresh()->kyc_status)->toBe('verified');
});

test('customer detail page renders with gl balance', function () {
    ['company' => $company] = seedLoanCompany();

    $this->post(route('loans.customers.store'), validCustomerPayload($company))->assertRedirect();
    $customer = LoanCustomer::firstOrFail();

    $response = $this->get(route('loans.customers.show', ['customer' => $customer->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Loan/Customers/Show')
        ->where('customer.customer_no', $customer->customer_no)
        ->where('customer.account_code', $customer->customer_no)
        ->has('recentEntries', 0)
    );
});

test('update keeps the auto-created gl account name in sync', function () {
    ['company' => $company] = seedLoanCompany();

    $this->post(route('loans.customers.store'), validCustomerPayload($company))->assertRedirect();
    $customer = LoanCustomer::firstOrFail();

    $this->put(route('loans.customers.update', ['customer' => $customer->id]), [
        ...validCustomerPayload($company),
        'first_name' => 'Adaeze-Renamed',
    ])->assertRedirect();

    expect($customer->fresh()->first_name)->toBe('Adaeze-Renamed');
    expect(Account::find($customer->fresh()->account_id)->name)->toContain('Adaeze-Renamed');
});

test('destroy refuses a customer with gl postings', function () {
    ['company' => $company] = seedLoanCompany();

    $this->post(route('loans.customers.store'), validCustomerPayload($company))->assertRedirect();
    $customer = LoanCustomer::firstOrFail();

    JournalEntry::create([
        'type' => 'debit',
        'value' => 1000,
        'balance_after' => 1000,
        'journal_transaction_id' => 1,
        'account_id' => $customer->account_id,
    ]);

    $this->delete(route('loans.customers.destroy', ['customer' => $customer->id]))->assertRedirect();
    expect(LoanCustomer::withTrashed()->find($customer->id))->not->toBeNull();
});

test('search finds customers by name or number', function () {
    ['company' => $company] = seedLoanCompany();

    $this->post(route('loans.customers.store'), validCustomerPayload($company))->assertRedirect();
    $customer = LoanCustomer::firstOrFail();

    $response = $this->get(route('loans.customers.search', ['q' => 'Adaeze']));
    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.customer_no'))->toBe($customer->customer_no);
});
