<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
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

        $order = Order::create([
            'order_number' => 'ORD-' . now()->timestamp,
            'table_id'     => $validated['table_id'] ?? null,
            'staff_id'     => auth('api')->id(),
            'order_type'   => $validated['order_type'],
            'guest_count'  => $validated['guest_count'] ?? 1,
            'notes'        => $validated['notes'] ?? null,
            'status'       => 'open'
        ]);

        return response()->json([
            'success' => true,
            'data' => $order
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | Show Order With Items + Calculated Total
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
    | Internal: Check Modification Permission
    |--------------------------------------------------------------------------
    */
    private function canModify(Order $order)
    {
        if (!in_array($order->status, ['billed', 'cancelled'])) {
            return true;
        }

        $user = auth('api')->user();

        return $user && $user->role === 'admin';
    }

    /*
    |--------------------------------------------------------------------------
    | Add Item To Order
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

        if (!$this->canModify($order)) {
            return response()->json([
                'success' => false,
                'message' => "Cannot modify a {$order->status} order"
            ], 409);
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

            $item = OrderItem::create([
                'order_id'     => $order->id,
                'menu_item_id' => $validated['menu_item_id'],
                'item_name'    => $validated['item_name'],
                'unit_price'   => $validated['unit_price'],
                'quantity'     => $validated['quantity'],
                'notes'        => $validated['notes'] ?? null,
                'status'       => 'pending'
            ]);

            DB::commit();

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
    | Update Item Quantity
    |--------------------------------------------------------------------------
    */
    public function updateItem(Request $request, $orderId, $itemId)
    {
        $order = Order::find($orderId);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        if (!$this->canModify($order)) {
            return response()->json([
                'success' => false,
                'message' => "Cannot modify a {$order->status} order"
            ], 409);
        }

        $item = OrderItem::where('order_id', $orderId)
            ->where('id', $itemId)
            ->first();

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Item not found'], 404);
        }

        $validated = $request->validate([
            'quantity' => 'required|integer|min:1'
        ]);

        $item->quantity = $validated['quantity'];
        $item->save();

        return response()->json([
            'success' => true,
            'message' => 'Item updated',
            'data' => $item
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Void Item
    |--------------------------------------------------------------------------
    */
    public function voidItem($orderId, $itemId)
    {
        $order = Order::find($orderId);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        if (!$this->canModify($order)) {
            return response()->json([
                'success' => false,
                'message' => "Cannot modify a {$order->status} order"
            ], 409);
        }

        $item = OrderItem::where('order_id', $orderId)
            ->where('id', $itemId)
            ->first();

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Item not found'], 404);
        }

        $item->status = 'voided';
        $item->save();

        return response()->json([
            'success' => true,
            'message' => 'Item voided'
        ]);
    }
}