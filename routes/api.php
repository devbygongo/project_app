<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\CreateController;

use App\Http\Controllers\ViewController;

use App\Http\Middleware\GetUserRole;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::prefix('admin')->middleware(['auth:sanctum', GetUserRole::class . ':admin'])->group(function () {

    Route::post('/add_user', [CreateController::class, 'user']);

    Route::get('/view_user', [ViewController::class, 'user']);

    Route::post('/add_product', [CreateController::class, 'product']);

    Route::get('/view_product', [ViewController::class, 'product']);

    Route::get('/get_product/{search?}', [ViewController::class, 'get_product']);

    Route::get('/category', [ViewController::class, 'categories']);

    Route::get('/subcategory/{category}', [ViewController::class, 'sub_categories']);

    Route::post('/add_order', [CreateController::class, 'orders']);

    Route::get('/view_order', [ViewController::class, 'orders']);
    
    Route::get('/view_user_order/{id}', [ViewController::class, 'orders_user_id']);
    
    Route::post('/add_order_items', [CreateController::class, 'orders_items']);
    
    Route::get('/view_order_items', [ViewController::class, 'order_items']);
    
    Route::get('/view_items_orders', [ViewController::class, 'orders_items_order_id']);

    Route::post('/add_cart', [CreateController::class, 'cart']);

    Route::get('/view_cart_user/{id}', [ViewController::class, 'cart_user']);

    Route::get('/logout', [CreateController::class, 'logout']);

});

Route::prefix('user')->middleware(['auth:sanctum', GetUserRole::class . ':user'])->group(function () {

    Route::get('/get_details', [ViewController::class, 'user_details']);

    Route::get('/logout', [CreateController::class, 'logout']);
});
Route::post('/login', [CreateController::class, 'login']);

Route::post('/register_user', [CreateController::class, 'user']);