<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');
Route::prefix('general-ledger')->group(function () {
    // Plain require: the application reboots between tests in one
    // process, and require_once would skip these routes on reboot.
    require __DIR__.'/modules/general_ledger.php';
});
Route::prefix('loans')->group(function () {
    // Plain require: the application reboots between tests in one
    // process, and require_once would skip these routes on reboot.
    require __DIR__.'/modules/loan.php';
});
Route::get('/dashboard', function () {
    return inertia('welcome', [
        'name' => 'Aje Damilola',
    ]);
});
