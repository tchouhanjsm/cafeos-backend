<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KitchenStation extends Model
{
    protected $fillable = [
        'name',
        'code',
        'is_active'
    ];
}