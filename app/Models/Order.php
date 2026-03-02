<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\OrderItem;

class Order extends Model
{
    protected $table = 'orders';

    // Your legacy table does NOT have created_at / updated_at
    public $timestamps = false;

    protected $fillable = [
        'order_number',
        'table_id',
        'staff_id',
        'order_type',
        'guest_count',
        'notes',
        'status'
    ];

    protected $casts = [
        'guest_count' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Business Logic
    |--------------------------------------------------------------------------
    */

    public function calculatedSubtotal()
    {
        return $this->items()
            ->where('status', '!=', 'voided')
            ->sum('subtotal');
    }
}