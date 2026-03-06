<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;

class KitchenController extends Controller
{

    /*
    |--------------------------------------------------------------------------
    | Kitchen Queue
    |--------------------------------------------------------------------------
    */

    public function queue()
    {

        $orders = Order::with(['items' => function ($q) {
            $q->whereIn('status', ['pending','cooking']);
        }])
        ->where('status','sent')
        ->orderBy('created_at','asc')
        ->get();

        return response()->json([
            'success'=>true,
            'count'=>$orders->count(),
            'data'=>$orders
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Start Cooking
    |--------------------------------------------------------------------------
    */

    public function startCooking($orderId,$itemId)
    {

        $item = OrderItem::where('order_id',$orderId)
            ->where('id',$itemId)
            ->first();

        if(!$item){
            return response()->json([
                'success'=>false,
                'message'=>'Item not found'
            ],404);
        }

        $item->status = 'cooking';
        $item->cooking_started_at = now();
        $item->save();

        return response()->json([
            'success'=>true,
            'message'=>'Cooking started'
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Mark Ready
    |--------------------------------------------------------------------------
    */

    public function markReady($orderId,$itemId)
    {

        $item = OrderItem::where('order_id',$orderId)
            ->where('id',$itemId)
            ->first();

        if(!$item){
            return response()->json([
                'success'=>false,
                'message'=>'Item not found'
            ],404);
        }

        $item->status = 'ready';
        $item->ready_at = now();
        $item->save();

        return response()->json([
            'success'=>true,
            'message'=>'Item ready'
        ]);
    }

/*
|--------------------------------------------------------------------------
| Kitchen Station Queue
|--------------------------------------------------------------------------
*/

public function stationQueue($stationId)
{

    $orders = Order::with(['items' => function ($q) use ($stationId) {

        $q->where('station_id', $stationId)
          ->whereIn('status', ['pending','cooking']);

    }])
    ->where('status','sent')
    ->orderBy('created_at','asc')
    ->get();

    return response()->json([
        'success' => true,
        'station_id' => $stationId,
        'count' => $orders->count(),
        'data' => $orders
    ]);

}

    /*
    |--------------------------------------------------------------------------
    | Serve Order
    |--------------------------------------------------------------------------
    */

    public function serve($orderId)
    {

        $order = Order::find($orderId);

        if(!$order){
            return response()->json([
                'success'=>false,
                'message'=>'Order not found'
            ],404);
        }

        foreach($order->items as $item){
            $item->served_at = now();
            $item->save();
        }

        $order->status = 'served';
        $order->save();

        return response()->json([
            'success'=>true,
            'message'=>'Order served'
        ]);
    }

}