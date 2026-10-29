<?php

namespace App\Models\GeneralLedger;

use App\Models\Company;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Guarded([])]
class Ledger extends Model
{
    // AKA Chart Of Accounts
    use HasUuids;

    protected $guarded = [];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function accounts()
    {
        return $this->hasMany(Account::class, 'ledger_id');
    }
}
