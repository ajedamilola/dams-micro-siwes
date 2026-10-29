<?php

use App\Models\GeneralLedger\Account;
use App\Models\GeneralLedger\JournalEntry;
use App\Models\Loan\Loan;

test('database seeder creates loans across statuses without raw customer GL credits', function () {
    $this->seed();

    $statuses = Loan::query()->distinct()->pluck('status')->all();

    expect($statuses)->toContain(
        'draft',
        'pending_approval',
        'approved',
        'active',
        'closed',
        'rejected',
        'written_off',
    );

    expect(Loan::query()->count())->toBe(8);

    expect(JournalEntry::query()
        ->whereHas('account', fn ($query) => $query->where('code', 'like', 'CUST-%'))
        ->count())->toBe(0);

    expect(JournalEntry::query()
        ->whereHas('account', fn ($query) => $query->where('code', 'like', 'LC-%'))
        ->count())->toBeGreaterThan(0);

    $receivable = Account::query()->where('code', '1120')->firstOrFail();
    $income = Account::query()->where('code', '4003')->firstOrFail();
    $revenue = Account::query()->where('code', '4000')->firstOrFail();

    expect($receivable->parent_id)->toBeNull()
        ->and($receivable->childAccounts()->count())->toBe(0)
        ->and($income->parent_id)->toBe($revenue->id)
        ->and($income->childAccounts()->count())->toBe(0)
        ->and($income->subledger_id)->toBeNull();
});
