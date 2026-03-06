<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderTimeline extends Model
{
    protected $table = 'order_timelines';

    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'staff_id',
        'event_type',
        'event_description'
    ];
}