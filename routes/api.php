<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\{Admin, Dealer, Distributor};
use App\Http\Controllers\Api\{AdminController, DealerController, DistributorController, ProductController, MarketingController, PostController, ReelController, ProfileController, PromotionalStockController, UserController};

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


// COMMON
Route::middleware('verify.token')->group(function () {
    
    Route::prefix('admin')->group(function () {
        Route::post('/send-otp', [AdminController::class, 'sendAdminOtp']);
        Route::post('/verify-otp', [AdminController::class, 'verifyAdminOtp']);
    });

    Route::get('get-support-details', [AdminController::class, 'getSupportDetails']);
    Route::prefix('user')->group(function () {
        Route::post('/send-otp', [UserController::class, 'sendUsetOtp']);
        Route::post('/verify-otp', [UserController::class, 'verifyUserOtp']);
        Route::get('/get-posts', [UserController::class, 'getPosts']);
        Route::get('/get-brochures', [UserController::class, 'getBrochures']);
        Route::get('/get-reels', [UserController::class, 'getReels']);
        Route::get('/get-leaflets', [UserController::class, 'getLeaflets']);
        Route::get('/get-videos', [UserController::class, 'getVideos']);
        Route::get('/get-products', [UserController::class, 'getproducts']);
        Route::get('/get-details', [UserController::class, 'getDetails']);
        Route::get('/get-subcategories', [UserController::class, 'getSubCatBasedOnCat']);
        Route::get('get-banners', [UserController::class, 'getBanners']);
    });

});

//ADMIN
Route::middleware('auth:admin-api')->prefix('admin')->group(function () {
    Route::post('update-support-details', [AdminController::class, 'updateSupportDetails']);
    Route::get('get-dashboard-data', [AdminController::class, 'getDashboardData']);
    Route::get('order-history', [AdminController::class, 'orderHistory']);
    Route::get('order-details', [AdminController::class, 'orderDetails']);
    Route::post('update-profile', [AdminController::class, 'updateProfile']);
    Route::get('get-profile', [AdminController::class, 'getProfile']);
    Route::get('get-distributor', [AdminController::class, 'getDistributor']);
    Route::post('add-update-banner', [AdminController::class, 'addUpdateBanner']);
    Route::get('get-banners', [AdminController::class, 'getBanners']);
    Route::post('delete-banner', [AdminController::class, 'deleteBanner']);
    Route::post('add-update-distributor', [AdminController::class, 'addUpdateDistributor']);
    Route::post('approve-decline-order', [AdminController::class, 'approveDeclineOrder']);
    Route::post('update-shipping-status', [AdminController::class, 'updateShippingStatus']);

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

    Route::post('stock-inward', [PromotionalStockController::class, 'stockInward']);
    Route::post('stock-outward', [PromotionalStockController::class, 'stockOutward']);
    

});

// DISTRIBUTOR
Route::middleware('auth:distributor-api')->prefix('distributor')->group(function () {
    Route::post('add-to-cart', [DistributorController::class, 'addToCart']);
    Route::get('view-cart', [DistributorController::class, 'viewCart']);
    Route::post('delete-cart', [DistributorController::class, 'deleteCart']);
    Route::post('place-order', [DistributorController::class, 'proceedToCheckout']);
    Route::get('order-history', [DistributorController::class, 'orderHistory']);
    Route::get('order-details', [DistributorController::class, 'orderDetails']);
    Route::post('add-update-address', [DistributorController::class, 'addUpdateAddress']);
    Route::post('delete-address', [DistributorController::class, 'deleteAddress']);
    Route::get('get-addresses', [DistributorController::class, 'getAddresses']);
    Route::post('update-profile', [DistributorController::class, 'updateProfile']);

}); 

// DEALER
Route::middleware('auth:dealer-api')->prefix('dealer')->group(function () {
    Route::post('update-profile', [DealerController::class, 'updateProfile']);

});


