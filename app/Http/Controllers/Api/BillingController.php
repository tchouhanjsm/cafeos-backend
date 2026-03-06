<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class BillingController extends Controller
{
    private $taxRate = 5; // 5% inclusive

    /*
    |--------------------------------------------------------------------------
    | Generate Bill
    |--------------------------------------------------------------------------
    */
    public function bill(Request $request, $id)
    {
        $order = Order::with('items')->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        if ($order->status === 'closed') {
            return response()->json([
                'success' => false,
                'message' => 'Order already closed'
            ], 409);
        }

        $subtotal = $order->calculatedSubtotal();
        $discount = (float) $request->input('discount', 0);

        if ($discount < 0) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid discount'
            ], 422);
        }

        $discountedSubtotal = max($subtotal - $discount, 0);

        // Inclusive 5% tax
        $tax = round($discountedSubtotal * ($this->taxRate / (100 + $this->taxRate)), 2);

        $grandTotal = round($discountedSubtotal, 2);

        DB::beginTransaction();

        try {

            $order->subtotal = $subtotal;
            $order->tax_amount = $tax;
            $order->discount_amount = $discount;
            $order->grand_total = $grandTotal;
            $order->paid_amount = 0;
            $order->balance_amount = $grandTotal;
            $order->status = 'billed';
            $order->billed_at = now();
            $order->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'subtotal' => $subtotal,
                    'discount' => $discount,
                    'tax' => $tax,
                    'grand_total' => $grandTotal
                ]
            ]);

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
    | Register Payment
    |--------------------------------------------------------------------------
    */
    public function pay(Request $request, $id)
    {
        $order = Order::find($id);

        if (!$order || $order->status !== 'billed') {
            return response()->json([
                'success' => false,
                'message' => 'Order not ready for payment'
            ], 409);
        }

        $amount = (float) $request->input('amount');
        $method = $request->input('method');

        if (!$amount || $amount <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid payment amount'
            ], 422);
        }

        DB::beginTransaction();

        try {

            Payment::create([
                'order_id' => $order->id,
                'amount' => $amount,
                'method' => $method,
                'received_by' => auth('api')->id()
            ]);

            $order->paid_amount += $amount;
            $order->balance_amount = max($order->grand_total - $order->paid_amount, 0);

            if ($order->balance_amount == 0) {
                $order->status = 'closed';

                // Auto free table
                if ($order->table_id) {
                    DB::table('tables')
                        ->where('id', $order->table_id)
                        ->update(['status' => 'free']);
                }
            }

            $order->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'paid' => $order->paid_amount,
                'balance' => $order->balance_amount,
                'status' => $order->status
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