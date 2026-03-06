<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Shift;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class ShiftController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Open Shift
    |--------------------------------------------------------------------------
    */
    public function open(Request $request)
    {
        $existing = Shift::where('status', 'open')->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Shift already open'
            ], 409);
        }

        $shift = Shift::create([
            'opened_by' => auth('api')->id(),
            'opening_balance' => $request->input('opening_balance', 0),
            'status' => 'open',
            'opened_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'data' => $shift
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Close Shift
    |--------------------------------------------------------------------------
    */
    public function close(Request $request)
    {
        $shift = Shift::where('status', 'open')->first();

        if (!$shift) {
            return response()->json([
                'success' => false,
                'message' => 'No active shift'
            ], 409);
        }

        $orders = Order::where('shift_id', $shift->id)
            ->where('status', 'closed')
            ->get();

        $totalSales = $orders->sum('grand_total');
        $cashSales = DB::table('payments')
            ->join('orders', 'payments.order_id', '=', 'orders.id')
            ->where('orders.shift_id', $shift->id)
            ->where('payments.method', 'cash')
            ->sum('payments.amount');

        $closingBalance = (float) $request->input('closing_balance', 0);
        $difference = $closingBalance - $cashSales;

        $shift->closed_by = auth('api')->id();
        $shift->closing_balance = $closingBalance;
        $shift->expected_cash = $cashSales;
        $shift->difference = $difference;
        $shift->status = 'closed';
        $shift->closed_at = now();
        $shift->save();

        return response()->json([
            'success' => true,
            'summary' => [
                'total_sales' => $totalSales,
                'cash_sales' => $cashSales,
                'expected_cash' => $cashSales,
                'closing_balance' => $closingBalance,
                'difference' => $difference
            ]
        ]);
    }
}