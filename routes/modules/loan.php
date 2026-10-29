<?php

use App\Http\Controllers\Loan\LoanController;
use App\Http\Controllers\Loan\LoanCustomerController;
use App\Http\Controllers\Loan\LoanProductController;
use App\Http\Controllers\Loan\OverviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', [OverviewController::class, 'index'])->name('loans.overview');

Route::prefix('customers')->name('loans.customers.')->controller(LoanCustomerController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/', 'store')->name('store');
    Route::get('/search', 'search')->name('search');
    Route::get('/{customer}', 'show')->name('show');
    Route::put('/{customer}', 'update')->name('update');
    Route::delete('/{customer}', 'destroy')->name('destroy');
    Route::post('/{customer}/verify', 'verify')->name('verify');
    Route::post('/{customer}/review', 'markReview')->name('review');
});

Route::prefix('products')->name('loans.products.')->controller(LoanProductController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/', 'store')->name('store');
    Route::get('/{product}', 'show')->name('show');
    Route::put('/{product}', 'update')->name('update');
    Route::delete('/{product}', 'destroy')->name('destroy');
    Route::put('/{product}/mapping', 'updateMapping')->name('mapping');
});

Route::prefix('loans')->name('loans.loans.')->controller(LoanController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/', 'store')->name('store');
    Route::get('/{loan}', 'show')->name('show');
    Route::post('/{loan}/approve', 'approve')->name('approve');
    Route::post('/{loan}/reject', 'reject')->name('reject');
    Route::post('/{loan}/disburse', 'disburse')->name('disburse');
    Route::post('/{loan}/repay', 'repay')->name('repay');
});
