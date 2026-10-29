<?php

namespace App\Models\GeneralLedger;

use Illuminate\Database\Eloquent\Model;

#[Guarded([])]
class AccountOpeningBalance extends Model
{
    protected $guarded = [];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
