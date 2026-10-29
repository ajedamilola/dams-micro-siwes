<?php

use App\Models\Company;
use App\Models\GeneralLedger\Account;
use App\Models\GeneralLedger\JournalEntry;
use App\Models\GeneralLedger\JournalTransaction;
use App\Models\GeneralLedger\Ledger;
use Illuminate\Support\Str;

function seedTransactionLedger(): array
{
    $company = Company::create(['name' => 'Test Company', 'code' => 'TST']);
    $ledger = Ledger::create(['name' => 'Master Chart', 'code' => 'MC', 'company_id' => $company->id]);

    $cash = Account::create([
        'name' => 'Cash on Hand', 'code' => '1001', 'description' => 'Cash',
        'ledger_id' => $ledger->id, 'balance_type' => 'debit',
        'category' => 'CashAndCashEquivalents', 'status' => 'active',
    ]);
    $revenue = Account::create([
        'name' => 'Sales', 'code' => '4001', 'description' => 'Sales',
        'ledger_id' => $ledger->id, 'balance_type' => 'credit',
        'category' => 'OperatingRevenue', 'status' => 'active',
    ]);

    return compact('company', 'ledger', 'cash', 'revenue');
}

function seedDraftTransaction(Account $cash, Account $revenue): JournalTransaction
{
    $transaction = JournalTransaction::create([
        'description' => 'Test sale',
        'ref' => 'TXN-0001',
        'status' => 'DRAFT',
        'effective_date' => now(),
    ]);
    JournalEntry::create([
        'type' => 'debit', 'value' => 1000, 'balance_after' => 0,
        'journal_transaction_id' => $transaction->id, 'account_id' => $cash->id,
    ]);
    JournalEntry::create([
        'type' => 'credit', 'value' => 1000, 'balance_after' => 0,
        'journal_transaction_id' => $transaction->id, 'account_id' => $revenue->id,
    ]);

    return $transaction;
}

test('transactions index renders database rows with stats', function () {
    ['cash' => $cash, 'revenue' => $revenue] = seedTransactionLedger();
    seedDraftTransaction($cash, $revenue);

    $response = $this->get(route('general-ledger.transactions.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('GeneralLedger/Transactions/Index')
        ->has('transactions.data', 1)
        ->where('transactions.data.0.ref', 'TXN-0001')
        ->where('transactions.data.0.debit_total', '1000.0000')
        ->where('transactions.data.0.credit_total', '1000.0000')
        ->where('stats.total', 1)
        ->where('stats.draft', 1)
        ->has('filters')
    );
});

test('transactions index orders latest created first and cursor paginates', function () {
    ['cash' => $cash, 'revenue' => $revenue] = seedTransactionLedger();
    $base = now()->startOfDay();

    for ($i = 1; $i <= 12; $i++) {
        $transaction = JournalTransaction::create([
            'description' => "Batch sale {$i}",
            'ref' => sprintf('TXN-%04d', $i),
            'status' => 'DRAFT',
            'effective_date' => (clone $base)->addDays($i),
            // Distinct days (not minutes): sqlite compares datetimes as
            // strings, so same-date times would re-match the whole page.
            'created_at' => (clone $base)->addDays($i),
            'updated_at' => (clone $base)->addDays($i),
        ]);
        JournalEntry::create([
            'type' => 'debit', 'value' => 100, 'balance_after' => 0,
            'journal_transaction_id' => $transaction->id, 'account_id' => $cash->id,
        ]);
    }

    $first = $this->get(route('general-ledger.transactions.index'));
    $first->assertOk();
    $page = $first->viewData('page')['props'];

    // Latest created first, 10 per page with a next cursor.
    expect($page['transactions']['data'])->toHaveCount(10);
    expect($page['transactions']['data'][0]['ref'])->toBe('TXN-0012');
    expect($page['transactions']['next_page_url'])->not->toBeNull();

    $second = $this->get($page['transactions']['next_page_url']);
    $second->assertOk();
    $remaining = $second->viewData('page')['props']['transactions']['data'];
    // The oldest rows are reachable past the cursor and paging terminates.
    // (On sqlite the cursor boundary row can repeat since datetimes compare
    // as strings there; Postgres compares them as timestamps.)
    $remainingRefs = collect($remaining)->pluck('ref')->all();
    expect($remainingRefs)->toContain('TXN-0001', 'TXN-0002');
    expect($second->viewData('page')['props']['transactions']['next_page_url'])->toBeNull();
});

test('transactions index filters by keyword and status', function () {
    ['cash' => $cash, 'revenue' => $revenue] = seedTransactionLedger();
    seedDraftTransaction($cash, $revenue);

    $byKeyword = $this->get(route('general-ledger.transactions.index', ['q' => 'test sale']));
    expect($byKeyword->viewData('page')['props']['transactions']['data'])->toHaveCount(1);

    $noMatch = $this->get(route('general-ledger.transactions.index', ['q' => 'nothing like this']));
    expect($noMatch->viewData('page')['props']['transactions']['data'])->toHaveCount(0);

    $byStatus = $this->get(route('general-ledger.transactions.index', ['status' => 'POSTED']));
    expect($byStatus->viewData('page')['props']['transactions']['data'])->toHaveCount(0);
});

test('transactions index defaults to latest created first', function () {
    seedTransactionLedger();

    JournalTransaction::create([
        'description' => 'Older created', 'ref' => 'TXN-0001', 'status' => 'DRAFT',
        'effective_date' => '2026-12-01', 'created_at' => '2026-01-10 10:00:00', 'updated_at' => '2026-01-10 10:00:00',
    ]);
    JournalTransaction::create([
        'description' => 'Newer created', 'ref' => 'TXN-0002', 'status' => 'DRAFT',
        'effective_date' => '2026-01-01', 'created_at' => '2026-06-10 10:00:00', 'updated_at' => '2026-06-10 10:00:00',
    ]);

    $refs = collect($this->get(route('general-ledger.transactions.index'))->viewData('page')['props']['transactions']['data'])->pluck('ref')->all();

    expect($refs)->toBe(['TXN-0002', 'TXN-0001']);
});

test('transactions index sorts by effective date in either direction', function () {
    seedTransactionLedger();

    JournalTransaction::create([
        'description' => 'Future dated', 'ref' => 'TXN-0001', 'status' => 'DRAFT',
        'effective_date' => '2026-12-01', 'created_at' => '2026-01-10 10:00:00', 'updated_at' => '2026-01-10 10:00:00',
    ]);
    JournalTransaction::create([
        'description' => 'Past dated', 'ref' => 'TXN-0002', 'status' => 'DRAFT',
        'effective_date' => '2026-01-01', 'created_at' => '2026-06-10 10:00:00', 'updated_at' => '2026-06-10 10:00:00',
    ]);

    $ascending = collect($this->get(route('general-ledger.transactions.index', [
        'sort' => 'effective_date', 'direction' => 'asc',
    ]))->viewData('page')['props']['transactions']['data'])->pluck('ref')->all();
    expect($ascending)->toBe(['TXN-0002', 'TXN-0001']);

    $descending = collect($this->get(route('general-ledger.transactions.index', [
        'sort' => 'effective_date', 'direction' => 'desc',
    ]))->viewData('page')['props']['transactions']['data'])->pluck('ref')->all();
    expect($descending)->toBe(['TXN-0001', 'TXN-0002']);
});

test('transactions index rejects an invalid sort', function () {
    $this->get(route('general-ledger.transactions.index', ['sort' => 'ref']))->assertInvalid(['sort']);
    $this->get(route('general-ledger.transactions.index', ['direction' => 'sideways']))->assertInvalid(['direction']);
});

test('transaction detail renders entries with permissions', function () {
    ['cash' => $cash, 'revenue' => $revenue] = seedTransactionLedger();
    $transaction = seedDraftTransaction($cash, $revenue);

    $response = $this->get(route('general-ledger.transactions.show', ['transaction' => $transaction->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('GeneralLedger/Transactions/Show')
        ->where('transaction.ref', 'TXN-0001')
        ->has('transaction.entries', 2)
        ->where('permissions.editable', true)
        ->where('permissions.can_submit', true)
        ->where('permissions.can_cancel', true)
        ->where('permissions.can_post', false)
    );
});

test('unknown transaction id returns a 404', function () {
    $this->get(route('general-ledger.transactions.show', ['transaction' => (string) Str::uuid()]))->assertNotFound();
});

test('store creates a draft and redirects to its page', function () {
    seedTransactionLedger();

    $response = $this->post(route('general-ledger.transactions.store'), [
        'description' => 'New cash sale',
        'effective_date' => '2026-10-15',
    ]);

    $transaction = JournalTransaction::firstWhere('description', 'New cash sale');
    expect($transaction)->not->toBeNull();
    expect($transaction->status)->toBe('DRAFT');
    expect($transaction->ref)->toStartWith('TXN-');
    $response->assertRedirect(route('general-ledger.transactions.show', ['transaction' => $transaction->id]));
});

test('update edits a draft transaction', function () {
    ['cash' => $cash, 'revenue' => $revenue] = seedTransactionLedger();
    $transaction = seedDraftTransaction($cash, $revenue);

    $this->put(route('general-ledger.transactions.update', ['transaction' => $transaction->id]), [
        'description' => 'Renamed sale',
    ])->assertRedirect();

    expect(JournalTransaction::firstWhere('ref', 'TXN-0001')->description)->toBe('Renamed sale');
});

test('update submits a balanced transaction', function () {
    ['cash' => $cash, 'revenue' => $revenue] = seedTransactionLedger();
    $transaction = seedDraftTransaction($cash, $revenue);

    $this->put(route('general-ledger.transactions.update', ['transaction' => $transaction->id]), [
        'status' => 'SUBMITTED',
    ])->assertRedirect();

    expect(JournalTransaction::firstWhere('ref', 'TXN-0001')->status)->toBe('SUBMITTED');
});

test('update refuses to submit an unbalanced transaction', function () {
    ['cash' => $cash] = seedTransactionLedger();

    $transaction = JournalTransaction::create([
        'description' => 'One sided', 'ref' => 'TXN-0001',
        'status' => 'DRAFT', 'effective_date' => now(),
    ]);
    JournalEntry::create([
        'type' => 'debit', 'value' => 1000, 'balance_after' => 0,
        'journal_transaction_id' => $transaction->id, 'account_id' => $cash->id,
    ]);

    $this->put(route('general-ledger.transactions.update', ['transaction' => $transaction->id]), [
        'status' => 'SUBMITTED',
    ])->assertInvalid(['status']);

    expect($transaction->fresh()->status)->toBe('DRAFT');
});

test('post moves a submitted transaction to posted with balances', function () {
    ['cash' => $cash, 'revenue' => $revenue] = seedTransactionLedger();
    $transaction = seedDraftTransaction($cash, $revenue);
    $transaction->update(['status' => 'SUBMITTED']);

    $this->post(route('general-ledger.transactions.post', ['transaction' => $transaction->id]))
        ->assertRedirect();

    $transaction->refresh();
    expect($transaction->status)->toBe('POSTED');
    expect($transaction->posted_at)->not->toBeNull();

    $entries = $transaction->entries()->orderBy('id')->get();
    expect(bccomp((string) $entries[0]->balance_after, '1000.0000', 4))->toBe(0);
    expect(bccomp((string) $entries[1]->balance_after, '1000.0000', 4))->toBe(0);
});

test('post rejects a transaction that is not submitted', function () {
    ['cash' => $cash, 'revenue' => $revenue] = seedTransactionLedger();
    $transaction = seedDraftTransaction($cash, $revenue);

    $this->post(route('general-ledger.transactions.post', ['transaction' => $transaction->id]))
        ->assertInvalid(['status']);

    expect($transaction->fresh()->status)->toBe('DRAFT');
});

test('reverse books a mirrored posted transaction and nets balances back', function () {
    ['cash' => $cash, 'revenue' => $revenue] = seedTransactionLedger();
    $transaction = seedDraftTransaction($cash, $revenue);
    $transaction->update(['status' => 'SUBMITTED']);
    $transaction->postTransaction();

    $response = $this->post(route('general-ledger.transactions.reverse', ['transaction' => $transaction->id]));
    $reversal = JournalTransaction::where('old_reversed_transaction_id', $transaction->id)->first();

    expect($reversal)->not->toBeNull();
    $response->assertRedirect(route('general-ledger.transactions.show', ['transaction' => $reversal->id]));
    expect($transaction->fresh()->status)->toBe('REVERSED');
    expect($reversal->status)->toBe('POSTED');
    expect($reversal->description)->toBe('Reversal of TXN-0001');

    $mirrored = $reversal->entries()->orderBy('id')->get();
    expect($mirrored[0]->type)->toBe('credit');
    expect($mirrored[0]->account_id)->toBe($cash->id);
    expect($mirrored[1]->type)->toBe('debit');
    expect($mirrored[1]->account_id)->toBe($revenue->id);

    expect((float) $cash->fresh()->account_balance)->toBe(0.0);
    expect((float) $revenue->fresh()->account_balance)->toBe(0.0);
});

test('reverse rejects a transaction that is not posted', function () {
    ['cash' => $cash, 'revenue' => $revenue] = seedTransactionLedger();
    $transaction = seedDraftTransaction($cash, $revenue);

    $this->post(route('general-ledger.transactions.reverse', ['transaction' => $transaction->id]))
        ->assertInvalid(['transaction']);

    expect($transaction->fresh()->status)->toBe('DRAFT');
    expect(JournalTransaction::whereNotNull('old_reversed_transaction_id')->count())->toBe(0);
});

test('reversed transactions link each other on the detail page', function () {
    ['cash' => $cash, 'revenue' => $revenue] = seedTransactionLedger();
    $transaction = seedDraftTransaction($cash, $revenue);
    $transaction->update(['status' => 'SUBMITTED']);
    $transaction->postTransaction();

    $posted = $this->get(route('general-ledger.transactions.show', ['transaction' => $transaction->id]));
    $posted->assertInertia(fn ($page) => $page
        ->where('permissions.can_reverse', true)
        ->where('reversed_by', null)
    );

    $this->post(route('general-ledger.transactions.reverse', ['transaction' => $transaction->id]));
    $reversal = JournalTransaction::where('old_reversed_transaction_id', $transaction->id)->first();

    $this->get(route('general-ledger.transactions.show', ['transaction' => $transaction->id]))
        ->assertInertia(fn ($page) => $page
            ->where('transaction.status', 'REVERSED')
            ->where('reversed_by.ref', $reversal->ref)
            ->where('permissions.can_reverse', false)
        );

    $this->get(route('general-ledger.transactions.show', ['transaction' => $reversal->id]))
        ->assertInertia(fn ($page) => $page
            ->where('transaction.status', 'POSTED')
            ->where('reverses.ref', 'TXN-0001')
        );
});
test('update cancels a draft and a submitted transaction', function () {
    ['cash' => $cash, 'revenue' => $revenue] = seedTransactionLedger();
    $transaction = seedDraftTransaction($cash, $revenue);

    $this->put(route('general-ledger.transactions.update', ['transaction' => $transaction->id]), [
        'status' => 'CANCELLED',
    ])->assertRedirect();

    expect(JournalTransaction::firstWhere('ref', 'TXN-0001')->status)->toBe('CANCELLED');
});

test('update rejects an invalid status transition', function () {
    ['cash' => $cash, 'revenue' => $revenue] = seedTransactionLedger();
    $transaction = seedDraftTransaction($cash, $revenue);
    $transaction->update(['status' => 'POSTED']);

    $this->put(route('general-ledger.transactions.update', ['transaction' => $transaction->id]), [
        'status' => 'SUBMITTED',
    ])->assertInvalid(['status']);
});

test('destroy deletes a draft with its entries', function () {
    ['cash' => $cash, 'revenue' => $revenue] = seedTransactionLedger();
    $transaction = seedDraftTransaction($cash, $revenue);

    $this->delete(route('general-ledger.transactions.destroy', ['transaction' => $transaction->id]))
        ->assertRedirect(route('general-ledger.transactions.index'));

    expect(JournalTransaction::firstWhere('ref', 'TXN-0001'))->toBeNull();
    expect(JournalEntry::count())->toBe(0);
});

test('destroy refuses a non-draft transaction', function () {
    ['cash' => $cash, 'revenue' => $revenue] = seedTransactionLedger();
    $transaction = seedDraftTransaction($cash, $revenue);
    $transaction->update(['status' => 'SUBMITTED']);

    $this->delete(route('general-ledger.transactions.destroy', ['transaction' => $transaction->id]))
        ->assertRedirect();

    expect(JournalTransaction::firstWhere('ref', 'TXN-0001'))->not->toBeNull();
});

test('entries can be added, edited and removed on a draft', function () {
    ['cash' => $cash, 'revenue' => $revenue] = seedTransactionLedger();
    $transaction = seedDraftTransaction($cash, $revenue);

    $this->post(route('general-ledger.entries.store', ['transaction' => $transaction->id]), [
        'account_id' => $cash->id, 'type' => 'debit', 'value' => 500,
    ])->assertRedirect();
    expect(JournalEntry::count())->toBe(3);

    $entry = JournalEntry::orderByDesc('id')->first();
    $this->put(route('general-ledger.entries.update', ['transaction' => $transaction->id, 'entry' => $entry->id]), [
        'value' => 600,
    ])->assertRedirect();
    expect(bccomp((string) $entry->fresh()->value, '600.0000', 4))->toBe(0);

    $this->delete(route('general-ledger.entries.destroy', ['transaction' => $transaction->id, 'entry' => $entry->id]))
        ->assertRedirect();
    expect(JournalEntry::count())->toBe(2);
});

test('entries from another transaction return a 404', function () {
    ['cash' => $cash, 'revenue' => $revenue] = seedTransactionLedger();
    $transaction = seedDraftTransaction($cash, $revenue);
    $other = JournalTransaction::create([
        'description' => 'Other draft', 'ref' => 'TXN-0002',
        'status' => 'DRAFT', 'effective_date' => now(),
    ]);
    $entry = $transaction->entries()->first();

    $this->put(route('general-ledger.entries.update', ['transaction' => $other->id, 'entry' => $entry->id]), [
        'value' => 600,
    ])->assertNotFound();
});

test('entries are rejected once the transaction leaves draft', function () {
    ['cash' => $cash, 'revenue' => $revenue] = seedTransactionLedger();
    $transaction = seedDraftTransaction($cash, $revenue);
    $transaction->update(['status' => 'SUBMITTED']);

    $this->post(route('general-ledger.entries.store', ['transaction' => $transaction->id]), [
        'account_id' => $cash->id, 'type' => 'debit', 'value' => 500,
    ])->assertInvalid(['entry']);
});
