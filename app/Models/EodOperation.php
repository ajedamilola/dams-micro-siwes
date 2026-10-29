<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EodOperation extends Model
{
    public function casts()
    {
        return [
            'failure_reason' => 'array',
        ];
    }
}
