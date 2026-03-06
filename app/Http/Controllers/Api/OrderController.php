<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderTimeline;
use App\Models\Shift;
use App\Models\MenuItem;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{

    /*
    |--------------------------------------------------------------------------
    | Timeline Logger
    |--------------------------------------------------------------------------
    */

    private function logEvent($orderId, $type, $description = null)
    {
        OrderTimeline::create([
            'order_id' => $orderId,
            'staff_id' => auth('api')->id(),
            'event_type' => $type,
            'event_description' => $description
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Kitchen Load Balancer
    |--------------------------------------------------------------------------
    */

    private function getBalancedStation($groupId)
    {
        $station = DB::table('kitchen_stations')
            ->leftJoin('order_items', function ($join) {
                $join->on('kitchen_stations.id', '=', 'order_items.station_id')
                     ->whereIn('order_items.status', ['pending', 'cooking']);
            })
            ->select(
                'kitchen_stations.id',
                DB::raw('COUNT(order_items.id) as workload')
            )
            ->where('kitchen_stations.group_id', $groupId)
            ->groupBy('kitchen_stations.id')
            ->orderBy('workload', 'asc')
            ->first();

        return $station->id ?? null;
    }


    /*
    |--------------------------------------------------------------------------
    | List Orders
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $orders = Order::withCount('items')
            ->latest()
            ->take(50)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Create Order
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {

        $validated = $request->validate([
            'order_type'  => 'required|string',
            'table_id'    => 'nullable|integer',
            'guest_count' => 'nullable|integer|min:1',
            'notes'       => 'nullable|string'
        ]);

        /*
        |--------------------------------------------------------------------------
        | Shift System
        |--------------------------------------------------------------------------
        */

        $shiftEnabled = DB::table('settings')
            ->where('key_name', 'shift_enabled')
            ->value('value');

        $shiftId = null;

        if ($shiftEnabled == 1) {

            $shift = Shift::where('opened_by', auth('api')->id())
                ->where('status', 'open')
                ->first();

            if (!$shift) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active shift. Please open shift first.'
                ], 409);
            }

            $shiftId = $shift->id;
        }

        /*
        |--------------------------------------------------------------------------
        | Create Order
        |--------------------------------------------------------------------------
        */

        $order = Order::create([
            'order_number' => 'ORD-' . now()->timestamp,
            'table_id'     => $validated['table_id'] ?? null,
            'staff_id'     => auth('api')->id(),
            'shift_id'     => $shiftId,
            'order_type'   => $validated['order_type'],
            'guest_count'  => $validated['guest_count'] ?? 1,
            'notes'        => $validated['notes'] ?? null,
            'status'       => 'open'
        ]);

        $this->logEvent($order->id, 'order_created', 'Order created');

        return response()->json([
            'success' => true,
            'data' => $order
        ], 201);
    }


    /*
    |--------------------------------------------------------------------------
    | Billing Queue
    |--------------------------------------------------------------------------
    */

    public function billingQueue()
    {
        $orders = Order::where('status', 'ready_for_billing')
            ->with('items')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'count' => $orders->count(),
            'data' => $orders
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Request Bill
    |--------------------------------------------------------------------------
    */

    public function requestBill($id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        if (!in_array($order->status, ['served','sent'])) {
            return response()->json([
                'success' => false,
                'message' => 'Order not eligible for billing request'
            ], 409);
        }

        $order->status = 'ready_for_billing';
        $order->save();

        $this->logEvent($order->id, 'bill_requested', 'Bill requested');

        return response()->json([
            'success' => true,
            'message' => 'Bill requested successfully'
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Show Order
    |--------------------------------------------------------------------------
    */

    public function show($id)
    {
        $order = Order::with('items')->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $order,
            'calculated_subtotal' => $order->calculatedSubtotal()
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Timeline
    |--------------------------------------------------------------------------
    */

    public function timeline($id)
    {
        $events = OrderTimeline::where('order_id', $id)
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $events
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Add Item (WITH LOAD BALANCER)
    |--------------------------------------------------------------------------
    */

    public function addItem(Request $request, $id)
    {

        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        $validated = $request->validate([
            'menu_item_id' => 'required|integer',
            'item_name'    => 'required|string',
            'unit_price'   => 'required|numeric|min:0',
            'quantity'     => 'required|integer|min:1',
            'notes'        => 'nullable|string'
        ]);

        DB::beginTransaction();

        try {

            /*
            |--------------------------------------------------------------------------
            | Find Menu Item
            |--------------------------------------------------------------------------
            */

            $menuItem = MenuItem::find($validated['menu_item_id']);

            $stationId = null;

            if ($menuItem && $menuItem->station_group_id) {
                $stationId = $this->getBalancedStation($menuItem->station_group_id);
            }

            /*
            |--------------------------------------------------------------------------
            | Create Item
            |--------------------------------------------------------------------------
            */

            $item = OrderItem::create([
                'order_id'     => $order->id,
                'menu_item_id' => $validated['menu_item_id'],
                'station_id'   => $stationId,
                'item_name'    => $validated['item_name'],
                'unit_price'   => $validated['unit_price'],
                'quantity'     => $validated['quantity'],
                'notes'        => $validated['notes'] ?? null,
                'status'       => 'pending'
            ]);

            DB::commit();

            $this->logEvent(
                $order->id,
                'item_added',
                $validated['item_name'] . ' x' . $validated['quantity']
            );

            return response()->json([
                'success' => true,
                'data' => $item
            ], 201);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Send To Kitchen
    |--------------------------------------------------------------------------
    */

    public function sendToKitchen($id)
    {

        $order = Order::with('items')->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        if ($order->status !== 'open') {
            return response()->json([
                'success' => false,
                'message' => 'Only open orders can be sent to kitchen'
            ], 409);
        }

        $pendingItems = $order->items()->where('status', 'pending')->get();

        if ($pendingItems->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No pending items to send'
            ], 409);
        }

        DB::beginTransaction();

        try {

            $order->items()
                ->where('status', 'pending')
                ->update(['status' => 'cooking']);

            $order->status = 'sent';
            $order->save();

            DB::commit();

            $this->logEvent($order->id, 'sent_to_kitchen', 'Order sent to kitchen');

            return response()->json([
                'success' => true,
                'message' => 'Order sent to kitchen'
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

}