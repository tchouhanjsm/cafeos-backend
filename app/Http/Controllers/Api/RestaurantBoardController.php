<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;

class RestaurantBoardController extends Controller
{

    /*
    |--------------------------------------------------------------------------
    | Live Restaurant Board
    |--------------------------------------------------------------------------
    */

    public function index()
    {

        /*
        |--------------------------------------------------------------------------
        | Tables / Orders Status
        |--------------------------------------------------------------------------
        */

        $tables = Order::select('id','order_number','table_id','status','created_at')
            ->whereNotIn('status',['paid','closed','cancelled'])
            ->orderBy('created_at','asc')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Kitchen Queue
        |--------------------------------------------------------------------------
        */

        $kitchen = OrderItem::with('order')
            ->whereIn('status',['pending','cooking'])
            ->orderBy('created_at','asc')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Billing Queue
        |--------------------------------------------------------------------------
        */

        $billing = Order::select('id','order_number','table_id','status')
            ->where('status','ready_for_billing')
            ->orderBy('updated_at','asc')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Alerts
        |--------------------------------------------------------------------------
        */

        $alerts = [];

        foreach ($tables as $order) {

            $minutes = now()->diffInMinutes($order->created_at);

            if ($minutes > 20 && $order->status != 'served') {

                $alerts[] = [
                    'type' => 'delay',
                    'message' => 'Order '.$order->order_number.' waiting too long'
                ];

            }

        }

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'success' => true,
            'tables' => $tables,
            'kitchen' => $kitchen,
            'billing' => $billing,
            'alerts' => $alerts
        ]);

    }

}