<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\BillingController;
use App\Http\Controllers\Api\ShiftController;
use App\Http\Controllers\Api\KitchenController;
use App\Http\Controllers\Api\KitchenStationController;


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

    // List Orders
    Route::get('/orders', [OrderController::class, 'index']);

    // Create Order
    Route::post('/orders', [OrderController::class, 'store'])
        ->middleware('role:admin,cashier,waiter');

    // View Single Order
    Route::get('/orders/{id}', [OrderController::class, 'show']);

    // Add Item
    Route::post('/orders/{id}/items', [OrderController::class, 'addItem'])
        ->middleware('role:admin,cashier,waiter');

    // Update Item
    Route::put('/orders/{order}/items/{item}', [OrderController::class, 'updateItem'])
        ->middleware('role:admin,cashier,waiter');

    // Void Item
    Route::put('/orders/{order}/items/{item}/void', [OrderController::class, 'voidItem'])
        ->middleware('role:admin,cashier');

    // Send To Kitchen
    Route::post('/orders/{id}/send', [OrderController::class, 'sendToKitchen'])
        ->middleware('role:admin,cashier,waiter');

    // Waiter requests bill
    Route::post('/orders/{id}/request-bill', [OrderController::class, 'requestBill'])
        ->middleware('role:admin,waiter');

    // Cashier Billing Queue
    Route::get('/cashier/billing-queue', [OrderController::class, 'billingQueue'])
        ->middleware('role:admin,cashier');

/*
|--------------------------------------------------------------------------
| Kitchen Display System (KDS)
|--------------------------------------------------------------------------
*/

// View kitchen queue
Route::get('/kitchen/queue', [KitchenController::class, 'queue'])
    ->middleware('role:admin,waiter,cashier,chef');

// Start cooking item
Route::post('/kitchen/order/{order}/item/{item}/start', [KitchenController::class, 'startCooking'])
    ->middleware('role:admin,waiter,cashier,chef');

// Mark item ready
Route::post('/kitchen/order/{order}/item/{item}/ready', [KitchenController::class, 'markReady'])
    ->middleware('role:admin,waiter,cashier,chef');

// Mark order served
Route::post('/kitchen/order/{order}/serve', [KitchenController::class, 'serve'])
    ->middleware('role:admin,waiter,cashier,chef');

    Route::get('/kitchen/station/{station}/queue', [KitchenController::class,'stationQueue'])
    ->middleware('role:admin,waiter,cashier,chef');

    /*
|--------------------------------------------------------------------------
| Kitchen Stations
|--------------------------------------------------------------------------
*/

Route::get('/kitchen/stations', [KitchenStationController::class,'index'])
    ->middleware('role:admin');

Route::post('/kitchen/stations', [KitchenStationController::class,'store'])
    ->middleware('role:admin');


    /*
    |--------------------------------------------------------------------------
    | Billing Module
    |--------------------------------------------------------------------------
    */

    // Generate Bill
    Route::post('/orders/{id}/bill', [BillingController::class, 'bill'])
        ->middleware('role:admin,cashier');

    // Register Payment
    Route::post('/orders/{id}/pay', [BillingController::class, 'pay'])
        ->middleware('role:admin,cashier');


    /*
    |--------------------------------------------------------------------------
    | Shift Management
    |--------------------------------------------------------------------------
    */

    // Open shift
    Route::post('/shift/open', [ShiftController::class, 'open'])
        ->middleware('role:admin,cashier');

    // Close shift
    Route::post('/shift/close', [ShiftController::class, 'close'])
        ->middleware('role:admin,cashier');

});