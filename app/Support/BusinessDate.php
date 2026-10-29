<?php

namespace App\Support;

use App\Enums\GeneralLedger\TransactionStatus;
use App\Models\GeneralLedger\JournalTransaction;
use App\Models\SystemSettings;
use Carbon\CarbonImmutable;

/**
 * The business date drives posting cut-off, loan accruals and every
 * date picker. It only moves forward, and only once the queue of
 * unposted journals is clear.
 */
final class BusinessDate
{
    public static function current(): CarbonImmutable
    {
        $value = SystemSettings::query()->where('name', 'business_date')->value('value');

        if (is_array($value)) {
            $value = $value['date'] ?? null;
        }

        return CarbonImmutable::parse($value ?: now()->toDateString())->startOfDay();
    }

    public static function today(): string
    {
        return self::current()->toDateString();
    }

    public static function unpostedCount(): int
    {
        return JournalTransaction::query()
            ->whereIn('status', [
                TransactionStatus::DRAFT->value,
                TransactionStatus::SUBMITTED->value,
            ])
            ->count();
    }

    public static function advance(CarbonImmutable $target): void
    {
        SystemSettings::query()->updateOrCreate(
            ['name' => 'business_date'],
            ['value' => $target->toDateString()],
        );
    }
}
