<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController;

/*
|--------------------------------------------------------------------------
| CafeOS API Routes
|--------------------------------------------------------------------------
| All routes here are automatically prefixed with /api
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::post('/auth/login', [AuthController::class, 'login']);

Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'CafeOS Laravel API running',
        'version' => 'v1'
    ]);
});


/*
|--------------------------------------------------------------------------
| Protected Routes (JWT Required)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:api'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Authenticated User Info
    |--------------------------------------------------------------------------
    */
    Route::get('/me', function () {
        $user = auth('api')->user();

        return response()->json([
            'success' => true,
            'staff' => [
                'id'    => (int) $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
            ]
        ]);
    });


    /*
    |--------------------------------------------------------------------------
    | Admin-only Test Route
    |--------------------------------------------------------------------------
    */
    Route::get('/admin-test', function () {
        return response()->json([
            'success' => true,
            'message' => 'Admin access granted'
        ]);
    })->middleware('role:admin');


    /*
    |--------------------------------------------------------------------------
    | Orders Module
    |--------------------------------------------------------------------------
    */

    // List Orders (all authenticated roles)
    Route::get('/orders', [OrderController::class, 'index']);

    // Create Order (admin, cashier, waiter)
    Route::post('/orders', [OrderController::class, 'store'])
        ->middleware('role:admin,cashier,waiter');

    // View Single Order
    Route::get('/orders/{id}', [OrderController::class, 'show']);

    // Add Item to Order (admin, cashier, waiter)
    Route::post('/orders/{id}/items', [OrderController::class, 'addItem'])
        ->middleware('role:admin,cashier,waiter');

    // Update Item Quantity (admin, cashier, waiter)
    Route::put('/orders/{order}/items/{item}', [OrderController::class, 'updateItem'])
        ->middleware('role:admin,cashier,waiter');

    // Void Item (admin, cashier only)
    Route::put('/orders/{order}/items/{item}/void', [OrderController::class, 'voidItem'])
        ->middleware('role:admin,cashier');

});