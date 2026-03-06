<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'orders';

    protected $fillable = [
        'order_number',
        'table_id',
        'staff_id',
        'order_type',
        'status',
        'guest_count',
        'notes',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'grand_total',
        'paid_amount',
        'balance_amount',
        'billed_at'
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function shift()
{
    return $this->belongsTo(Shift::class);
}

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
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