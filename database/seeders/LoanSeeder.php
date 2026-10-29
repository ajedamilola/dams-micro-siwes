<?php

namespace Database\Seeders;

use App\Enums\Loan\LoanStatus;
use App\Models\Company;
use App\Models\GeneralLedger\Account;
use App\Models\GeneralLedger\Ledger;
use App\Models\Loan\Loan;
use App\Models\Loan\LoanCustomer;
use App\Models\Loan\LoanProduct;
use App\Models\SystemSettings;
use App\Services\GeneralLedger\RebuildLedgerBalances;
use App\Services\Loan\LoanLifecycle;
use Carbon\CarbonImmutable;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use RuntimeException;

class LoanSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Loans across the full workflow rather than raw customer GL entries.
     * Each loan posts through LoanLifecycle, so customer accounts only move
     * because of real loan events (disbursement, accrual and repayment).
     */
    public function run(): void
    {
        $businessDate = CarbonImmutable::parse(
            SystemSettings::query()->where('name', 'business_date')->firstOrFail()->value,
        );

        if (Loan::query()->where('notes', 'like', 'Demo seed%')->exists()) {
            app(RebuildLedgerBalances::class)->run($businessDate);

            return;
        }

        $company = Company::query()->where('code', 'DEMO')->firstOrFail();
        $master = Ledger::query()->where('company_id', $company->id)->where('code', 'MC')->firstOrFail();
        $bank = Account::query()->where('ledger_id', $master->id)->where('code', '1002')->firstOrFail();

        $customers = LoanCustomer::query()
            ->where('company_id', $company->id)
            ->orderBy('customer_no')
            ->get()
            ->values();

        $products = LoanProduct::query()
            ->where('company_id', $company->id)
            ->get()
            ->keyBy('code');

        if ($customers->isEmpty() || $products->count() < 4) {
            throw new RuntimeException('Seed loan customers and products before LoanSeeder.');
        }

        foreach (['SAL', 'GRP', 'SME', 'EMG'] as $code) {
            if (! $products->has($code)) {
                throw new RuntimeException("Missing loan product [{$code}].");
            }
        }

        $lifecycle = app(LoanLifecycle::class);
        $customer = fn (int $index) => $customers[$index % $customers->count()];
        $product = fn (string $code) => $products->get($code);

        // Draft: booked but not yet submitted for approval.
        $draft = $lifecycle->book([
            'loan_product_id' => $product('SAL')->id,
            'loan_customer_id' => $customer(0)->id,
            'principal' => 80000,
            'tenor' => 3,
            'first_due_date' => $businessDate->addDays(30)->toDateString(),
            'notes' => 'Demo seed: draft application',
        ]);
        $draft->update(['status' => LoanStatus::Draft->value]);

        // Pending approval.
        $lifecycle->book([
            'loan_product_id' => $product('SAL')->id,
            'loan_customer_id' => $customer(1)->id,
            'principal' => 120000,
            'tenor' => 6,
            'first_due_date' => $businessDate->addDays(30)->toDateString(),
            'notes' => 'Demo seed: awaiting credit approval',
        ]);

        // Approved, not disbursed.
        $approved = $lifecycle->book([
            'loan_product_id' => $product('SME')->id,
            'loan_customer_id' => $customer(2)->id,
            'principal' => 200000,
            'tenor' => 12,
            'first_due_date' => $businessDate->addDays(30)->toDateString(),
            'notes' => 'Demo seed: approved, pending disbursement',
        ]);
        $lifecycle->approve($approved);

        // Rejected application.
        $rejected = $lifecycle->book([
            'loan_product_id' => $product('EMG')->id,
            'loan_customer_id' => $customer(3)->id,
            'principal' => 50000,
            'tenor' => 4,
            'first_due_date' => $businessDate->addDays(30)->toDateString(),
            'notes' => 'Demo seed: rejected on affordability',
        ]);
        $lifecycle->reject($rejected);

        // Active SME loan with the first instalment collected.
        $active = $lifecycle->book([
            'loan_product_id' => $product('SME')->id,
            'loan_customer_id' => $customer(4)->id,
            'principal' => 300000,
            'tenor' => 12,
            'first_due_date' => $businessDate->subDays(10)->toDateString(),
            'notes' => 'Demo seed: active with first instalment collected',
        ]);
        $lifecycle->approve($active);
        $lifecycle->disburse($active, (string) $bank->id, $businessDate->subDays(40)->toDateString());
        $firstInstalment = $active->fresh()->schedules()->firstOrFail();
        $lifecycle->repay(
            $active->fresh(),
            (string) $firstInstalment->total_due,
            (string) $bank->id,
            $businessDate->subDays(5)->toDateString(),
            'Demo seed: first instalment',
        );

        // Active weekly group loan with no collections yet.
        $weekly = $lifecycle->book([
            'loan_product_id' => $product('GRP')->id,
            'loan_customer_id' => $customer(5)->id,
            'principal' => 60000,
            'tenor' => 8,
            'first_due_date' => $businessDate->subDays(13)->toDateString(),
            'notes' => 'Demo seed: active group loan',
        ]);
        $lifecycle->approve($weekly);
        $lifecycle->disburse($weekly, (string) $bank->id, $businessDate->subDays(20)->toDateString());

        // Closed loan, fully repaid.
        $closed = $lifecycle->book([
            'loan_product_id' => $product('SAL')->id,
            'loan_customer_id' => $customer(6)->id,
            'principal' => 100000,
            'tenor' => 6,
            'first_due_date' => $businessDate->subDays(2)->toDateString(),
            'notes' => 'Demo seed: closed after full repayment',
        ]);
        $lifecycle->approve($closed);
        $lifecycle->disburse($closed, (string) $bank->id, $businessDate->subDays(12)->toDateString());
        $lifecycle->repay(
            $closed->fresh(),
            (string) $closed->fresh()->total_repayable,
            (string) $bank->id,
            $businessDate->subDays(1)->toDateString(),
            'Demo seed: full settlement',
        );

        // Written off after default.
        $writtenOff = $lifecycle->book([
            'loan_product_id' => $product('EMG')->id,
            'loan_customer_id' => $customer(7)->id,
            'principal' => 40000,
            'tenor' => 4,
            'first_due_date' => $businessDate->subDays(70)->toDateString(),
            'notes' => 'Demo seed: written off after default',
        ]);
        $lifecycle->approve($writtenOff);
        $lifecycle->disburse($writtenOff, (string) $bank->id, $businessDate->subDays(100)->toDateString());
        $writtenOff->update(['status' => LoanStatus::WrittenOff->value]);

        app(RebuildLedgerBalances::class)->run($businessDate);
    }
}
