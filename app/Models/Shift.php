<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    protected $fillable = [
        'opened_by',
        'closed_by',
        'opening_balance',
        'closing_balance',
        'expected_cash',
        'difference',
        'status',
        'opened_at',
        'closed_at'
    ];

    public $timestamps = false;

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}