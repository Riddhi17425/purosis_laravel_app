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

    Route::prefix('user')->group(function () {
        Route::post('/send-otp', [UserController::class, 'sendUsetOtp']);
        Route::post('/verify-otp', [UserController::class, 'verifyUserOtp']);

        Route::get('/get-post', [UserController::class, 'getPosts']);
        Route::get('/get-brochure', [UserController::class, 'getBrochures']);
        Route::get('/get-reel', [UserController::class, 'getReels']);
        Route::get('/get-leaflet', [UserController::class, 'getLeaflets']);
        Route::get('/get-details', [AdminController::class, 'getDetails']);
    });

});

//ADMIN
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

    Route::post('stock-inward', [PromotionalStockController::class, 'stockInward']);
    Route::post('stock-outward', [PromotionalStockController::class, 'stockOutward']);
    Route::post('add-update-distributor', [AdminController::class, 'addUpdateDistributor']);
    Route::get('get-distributor', [AdminController::class, 'getDistributor']);



});

// DISTRIBUTOR
Route::middleware('auth:distributor-api')->prefix('distributor')->group(function () {
    // Route::get('/profile', function (Request $request) {
    //     return $request->user(); // distributor info
    // });
    Route::get('get-subcategory/{category_id}', [AdminController::class, 'getSubCatBasedOnCat']);


    
}); 

// DEALER
Route::middleware('auth:dealer-api')->prefix('dealer')->group(function () {
    // Route::get('/profile', function (Request $request) {
    //     return $request->user(); // dealer info
    // });

});


