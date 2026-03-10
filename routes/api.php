<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\{Admin, Dealer, Distributor};
use App\Http\Controllers\Api\{AdminController, DealerController, DistributorController, ProductController, MarketingController, PostController, ReelController, ProfileController, PromotionalStockController};

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

    Route::post('add-update-profile', [AdminController::class, 'addUpdateProfile']);
    Route::get('get-profiles', [AdminController::class, 'getProfiles']);

    Route::post('add-update-category', [ProductController::class, 'addUpdateCategory']);
    Route::get('get-categories', [ProductController::class, 'getCategories']);
    Route::post('add-update-subcategory', [ProductController::class, 'addUpdateSubCategory']);
    Route::get('get-sub-categories', [ProductController::class, 'getSubcategories']);
    Route::post('add-update-product', [ProductController::class, 'addUpdateProduct']);
    Route::get('get-products', [ProductController::class, 'getProducts']);
    
    Route::post('add-update-brochure', [MarketingController::class, 'addUpdateBrochure']);
    Route::get('get-brochures', [MarketingController::class, 'getBrochures']);
    Route::post('add-update-video', [MarketingController::class, 'addUpdateVideo']);
    Route::get('get-videos', [MarketingController::class, 'getVideos']);
    Route::post('add-update-leaflet', [MarketingController::class, 'addUpdateLeaflet']);
    Route::get('get-leaflets', [MarketingController::class, 'getLeaflets']);

    Route::post('add-update-post', [PostController::class, 'addUpdatePost']);
    Route::get('get-posts', [PostController::class, 'getPosts']);

    Route::post('add-update-reel', [ReelController::class, 'addUpdateReel']);
    Route::get('get-reels', [ReelController::class, 'getReels']);

    // Route::post('add-update-profile', [ProfileController::class, 'addUpdateProfile']);
    // Route::get('get-profiles', [ProfileController::class, 'getProfiles']);

    Route::get('get-details', [AdminController::class, 'getDetails']);
    Route::post('stock-inward', [PromotionalStockController::class, 'stockInward']);
    Route::post('stock-outward', [PromotionalStockController::class, 'stockOutward']);


});

Route::prefix('distributor')->group(function () {

    Route::get('get-post', [DistributorController::class, 'getPosts']);
    Route::get('get-brochure', [DistributorController::class, 'getBrochures']);
    Route::get('get-reel', [DistributorController::class, 'getReels']);
    Route::get('get-leaflet', [DistributorController::class, 'getLeaflets']);
   
});

Route::post('user/send-otp', [AdminController::class, 'distributorSendAdminOtp']);
Route::post('user/verify-otp', [AdminController::class, 'distributorVerifyAdminOtp']);

// Distributor
Route::middleware('auth:distributor-api')->prefix('distributor')->group(function () {
    // Route::get('/profile', function (Request $request) {
    //     return $request->user(); // distributor info
    // });

    
}); 

// Dealer
Route::middleware('auth:dealer-api')->prefix('dealer')->group(function () {
    // Route::get('/profile', function (Request $request) {
    //     return $request->user(); // dealer info
    // });

});


