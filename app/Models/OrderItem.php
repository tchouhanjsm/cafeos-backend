<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Order;

class OrderItem extends Model
{
    protected $table = 'order_items';

    // Your legacy table does NOT have created_at / updated_at
    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'menu_item_id',
        'item_name',
        'unit_price',
        'quantity',
        'notes',
        'status'
    ];

    protected $casts = [
        'unit_price' => 'float',
        'quantity'   => 'integer',
        'subtotal'   => 'float', // generated column (read-only)
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}