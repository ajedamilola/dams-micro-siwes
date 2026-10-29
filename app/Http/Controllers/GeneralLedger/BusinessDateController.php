<?php

namespace App\Http\Controllers\GeneralLedger;

use App\Http\Controllers\Controller;
use App\Models\SystemSettings;
use App\Services\GeneralLedger\BalanceSnapshot;
use App\Support\BusinessDate;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BusinessDateController extends Controller
{
    public function advance(Request $request, BalanceSnapshot $snapshots)
    {
        $current = BusinessDate::current();

        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d', 'after:'.$current->toDateString()],
        ], [
            'date.after' => 'The business date can only move forward.',
        ]);

        $target = CarbonImmutable::parse($data['date'])->startOfDay();

        DB::transaction(function () use ($target, $snapshots) {
            // Lock the date row so two advances cannot interleave, then
            // re-check the date and the unposted queue inside the lock.
            $locked = SystemSettings::query()
                ->where('name', 'business_date')
                ->lockForUpdate()
                ->first();

            $lockedCurrent = CarbonImmutable::parse($locked?->value ?: now()->toDateString())->startOfDay();

            if ($target->lte($lockedCurrent)) {
                throw ValidationException::withMessages([
                    'date' => 'Another advance already moved the business date to this point or later.',
                ]);
            }

            $unposted = BusinessDate::unpostedCount();

            if ($unposted > 0) {
                throw ValidationException::withMessages([
                    'date' => "Post or cancel {$unposted} unposted journal(s) before moving the business date.",
                ]);
            }

            // Closing balances of the old day become the opening balances
            // of the new day. The snapshot service locks every account.
            $snapshots->closeInto($lockedCurrent, $target);

            BusinessDate::advance($target);
        });

        return back();
    }
}
