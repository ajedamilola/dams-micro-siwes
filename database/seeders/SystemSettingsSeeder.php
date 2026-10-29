<?php

namespace Database\Seeders;

use App\Models\SystemSettings;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SystemSettingsSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SystemSettings::query()->firstOrCreate(
            ['name' => 'business_date'],
            ['value' => now()->toDateString()],
        );
    }
}
