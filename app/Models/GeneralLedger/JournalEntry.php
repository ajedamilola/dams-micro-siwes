<?php

namespace App\Models\GeneralLedger;

use Illuminate\Database\Eloquent\Model;

#[Guarded([])]
class JournalEntry extends Model
{
    protected $guarded = [];

    //
    public function transaction()
    {
        return $this->belongsTo(JournalTransaction::class, 'journal_transaction_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function updateAccountBalance(array &$running_balances)
    {
        $account = $this->account;
        if (! isset($running_balances[$account->id])) {
            $running_balances[$account->id] = $account->account_balance;
        }
        $account_balance = $running_balances[$account->id];
        $mod_function = $account->balance_type == $this->type ? 'bcadd' : 'bcsub';
        $balance_after = $mod_function($account_balance, $this->value, 4);
        $running_balances[$account->id] = $balance_after;
        $this->update([
            'balance_after' => $balance_after,
        ]);
    }
}
