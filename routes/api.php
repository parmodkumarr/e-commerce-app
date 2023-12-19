<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['prefix' => 'auth'], function () {

    Route::post('login', [AuthController::class, 'login']);
    Route::post('signup', [AuthController::class, 'signup']);

    Route::group(['middleware' => 'auth:sanctum'], function () {
        Route::get('logout', [AuthController::class, 'logout']);
    });

});

	Route::apiResource('products', ProductController::class)->except(['update', 'store', 'destroy']);

	Route::group(['middleware' => 'auth:sanctum'], function () {
		
		Route::post('/carts', [CartController::class, 'store']);
		Route::post('/carts/{cartId}/products', [CartController::class, 'addProducts']);
		Route::get('/carts/{cartId}', [CartController::class, 'show']);
		Route::get('/carts/destroy', [CartController::class, 'destroy']);
		Route::delete('/carts/{cartId}/products', [CartController::class, 'removeProduct']);
		Route::post('/carts/{cart}/checkout', [CartController::class,'checkout']);
		
		Route::apiResource('orders', OrderController::class)->except(['update', 'destroy','store']);

	});

