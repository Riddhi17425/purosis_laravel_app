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

    Route::post('add-update-category', [ProductController::class, 'addUpdateCategory']);
    Route::get('get-categories', [ProductController::class, 'getCategories']);
    Route::post('add-update-subcategory', [ProductController::class, 'addUpdateSubCategory']);
    Route::get('get-sub-categories', [ProductController::class, 'getSubcategories']);
    Route::post('add-update-product', [ProductController::class, 'addUpdateProduct']);
    Route::get('get-products', [ProductController::class, 'getProducts']);
    
    Route::post('add-update-brochure', [MarketingController::class, 'addUpdateBrochure']);
    Route::get('get-brochures', [MarketingController::class, 'getBrochures']);

    Route::get('get-details', [AdminController::class, 'getDetails']);

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