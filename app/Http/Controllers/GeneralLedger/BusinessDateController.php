<?php

namespace App\Http\Controllers\GeneralLedger;

use App\Http\Controllers\Controller;
use App\Support\BusinessDate;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BusinessDateController extends Controller
{
    public function advance(Request $request)
    {
        $current = BusinessDate::current();

        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d', 'after:'.$current->toDateString()],
        ], [
            'date.after' => 'The business date can only move forward.',
        ]);

        $unposted = BusinessDate::unpostedCount();

        if ($unposted > 0) {
            throw ValidationException::withMessages([
                'date' => "Post or cancel {$unposted} unposted journal(s) before moving the business date.",
            ]);
        }

        BusinessDate::advance(CarbonImmutable::parse($data['date']));

        return back();
    }
}
