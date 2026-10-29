<?php

use App\Http\Controllers\GeneralLedger\AccountController;
use App\Http\Controllers\GeneralLedger\JournalEntryController;
use App\Http\Controllers\GeneralLedger\OverviewController;
use App\Http\Controllers\GeneralLedger\ReportController;
use App\Http\Controllers\GeneralLedger\SettingsController;
use App\Http\Controllers\GeneralLedger\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [OverviewController::class, 'index'])->name('general-ledger.overview');

Route::prefix('accounts')->name('general-ledger.accounts.')->controller(AccountController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/', 'store')->name('store');
    Route::get('/search', 'search')->name('search');
    Route::get('/{ledger}/{account}', 'show')->name('show');
    Route::put('/{ledger}/{account}', 'update')->name('update');
    Route::delete('/{ledger}/{account}', 'destroy')->name('destroy');
});

Route::prefix('transactions')->name('general-ledger.transactions.')->controller(TransactionController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/', 'store')->name('store');
    Route::get('/{transaction}', 'show')->name('show');
    Route::put('/{transaction}', 'update')->name('update');
    Route::delete('/{transaction}', 'destroy')->name('destroy');
    Route::post('/{transaction}/post', 'post')->name('post');
    Route::post('/{transaction}/reverse', 'reverse')->name('reverse');
});

Route::prefix('transactions/{transaction}/entries')->name('general-ledger.entries.')->controller(JournalEntryController::class)->group(function () {
    Route::post('/', 'store')->name('store');
    Route::put('/{entry}', 'update')->name('update');
    Route::delete('/{entry}', 'destroy')->name('destroy');
});

Route::controller(ReportController::class)->group(function () {
    Route::get('/trial-balance', 'trialBalance')->name('general-ledger.trial-balance');
    Route::get('/balance-sheet', 'balanceSheet')->name('general-ledger.balance-sheet');
});

Route::prefix('settings')->name('general-ledger.settings.')->controller(SettingsController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::put('/', 'update')->name('update');
});
