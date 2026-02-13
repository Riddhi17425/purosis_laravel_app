<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\{Admin, Dealer, Distributor};
use App\Http\Controllers\Api\{AdminController, DealerController, DistributorController, ProductController, MarketingController};

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });


//Admin
Route::post('admin/send-otp', [AdminController::class, 'sendAdminOtp']);
Route::post('admin/verify-otp', [AdminController::class, 'verifyAdminOtp']);
Route::middleware('auth:admin-api')->prefix('admin')->group(function () {

    Route::get('get-categories', [ProductController::class, 'getCategories']);
    Route::get('get-subcategories', [ProductController::class, 'getSubcategories']);
    Route::post('add-product', [ProductController::class, 'addProduct']);
    Route::get('get-products', [ProductController::class, 'getProducts']);

});

// Dealer
Route::middleware('auth:dealer-api')->prefix('dealer')->group(function () {
    Route::get('/profile', function (Request $request) {
        return $request->user(); // dealer info
    });
});

// Distributor
Route::middleware('auth:distributor-api')->prefix('distributor')->group(function () {
    Route::get('/profile', function (Request $request) {
        return $request->user(); // distributor info
    });
}); 