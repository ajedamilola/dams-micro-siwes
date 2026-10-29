<?php

namespace App\Models;

use App\Models\GeneralLedger\Ledger;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'code'])]
class Company extends Model
{
    public function ledgers()
    {
        return $this->hasMany(Ledger::class);
    }
}
