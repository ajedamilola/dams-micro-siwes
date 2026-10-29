<?php

use Inertia\Testing\AssertableInertia;

test('general ledger overview renders with default and custom ranges', function () {
    $this->get(route('general-ledger.overview'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('GeneralLedger/Overview')
            ->has('filters.from')
            ->has('filters.to')
            ->has('stats')
            ->has('activity')
            ->has('ledgers')
            ->has('top_accounts')
            ->has('recent')
        );

    $this->get(route('general-ledger.overview', [
        'from' => now()->subDays(7)->toDateString(),
        'to' => now()->toDateString(),
    ]))->assertOk();
});

test('loan overview renders with default and custom ranges', function () {
    $this->get(route('loans.overview'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Loan/Overview')
            ->has('filters.from')
            ->has('filters.to')
            ->has('stats')
            ->has('activity')
            ->has('portfolio')
            ->has('status')
            ->has('recent')
        );

    $this->get(route('loans.overview', [
        'from' => now()->subDays(7)->toDateString(),
        'to' => now()->toDateString(),
    ]))->assertOk();
});
