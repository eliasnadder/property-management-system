<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\OfficeController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\FavoriteController;

//------------------------Auth-------------------------------------------------
Route::post('/login', [UserController::class, 'login']);
Route::post('/registerUser', [UserController::class, 'registerUser']);
Route::post('/registerOffice', [OfficeController::class, 'registerOffice']);
Route::post('/logout', [UserController::class, 'logout'])->middleware('api');


//---------------------user-------------------------------------------------------------
Route::group(['middleware' => ['api', 'jwt.auth'], 'prefix' => 'user'], function () {
    Route::get('/showOffice/{Id}', [OfficeController::class, 'showOffice']);
    Route::get('/getFollowersCount/{Id}', [OfficeController::class, 'getFollowersCount']);
    Route::get('/followOffice/{Id}', [OfficeController::class, 'followOffice']);
    Route::get('/getAllOfficePropertyVideos/{id}', [OfficeController::class, 'getAllOfficePropertyVideos']);
    Route::get('/getOfficePropertyCount/{id}', [OfficeController::class, 'getOfficePropertyCount']);

    Route::get('/properties/availability', [PropertyController::class, 'availability']);
    Route::post('/payad', [PropertyController::class, 'receiveCard']);

    Route::post('/updateProfile', [UserController::class, 'updateProfile']);
    Route::get('/getProfile', [UserController::class, 'getProfile']);

    Route::post('/addToFavorites', [FavoriteController::class, 'addToFavorites']);
    Route::post('/removeFromFavorites', [FavoriteController::class, 'removeFromFavorites']);
    Route::get('/getFavorites', [FavoriteController::class, 'getFavorites']);
    Route::get('/is-favorited', [FavoriteController::class, 'isFavorited']);

    Route::get('/getOfficeViews/{Id}', [OfficeController::class, 'getOfficeViews']);
    Route::post('/rateOffice/{id}', [ReviewController::class, 'rateOffice']);
    Route::get('/getRating/{office_id}', [ReviewController::class, 'getRating']);

    Route::get('/showProperty/{Id}', [PropertyController::class, 'showProperty']);
    Route::get('/properties/search/{ad_number}', [PropertyController::class, 'searchByAdNumber']);
    Route::get('/properties/filter', [PropertyController::class, 'filter']);
    Route::get('/getAllOfficeProperties/{Id}', [OfficeController::class, 'getAllOfficeProperties']);
});

//--------------------------office--------------------------------------------------------
Route::group(['middleware' => ['auth:office-api', 'office'], 'prefix' => 'office'], function () {
    Route::get('/GetOfficeFollowers/{id}', [OfficeController::class, 'GetOfficeFollowers']);

    Route::post('/changePropertyStatus/{Id}', [PropertyController::class, 'changePropertyStatus']);
    Route::get('/showOffice/{Id}', [OfficeController::class, 'showOffice']);

    Route::get('/getFollowersCount/{Id}', [OfficeController::class, 'getFollowersCount']);

    Route::post('/propertyStore', [PropertyController::class, 'propertyStore']);

    Route::post('/requestSubscription', [OfficeController::class, 'requestSubscription']);
    Route::get('/getPendingRequestsOffice', [RequestController::class, 'getPendingRequestsOffice']);
    Route::get('/getacceptedRequestsOffice', [RequestController::class, 'getacceptedRequestsOffice']);
    Route::get('/getrejectedRequestsOffice', [RequestController::class, 'getrejectedRequestsOffice']);

    Route::get('/getOfficePropertyCount/{id}', [OfficeController::class, 'getOfficePropertyCount']);
    Route::get('/getAllOfficePropertyVideos/{id}', [OfficeController::class, 'getAllOfficePropertyVideos']);
    Route::get('/getOfficeViews/{Id}', [OfficeController::class, 'getOfficeViews']);

    Route::get('/getRating/{office_id}', [ReviewController::class, 'getRating']);
    Route::get('/getActiveSubscriptionsOffice', [RequestController::class, 'getActiveSubscriptionsOffice']);
    Route::get('/getRejectedSubscriptionsOffice', [RequestController::class, 'getRejectedSubscriptionsOffice']);
    Route::get('/getPendingSubscriptionsOffice', [RequestController::class, 'getPendingSubscriptionsOffice']);
    Route::get('/showProperty/{Id}', [PropertyController::class, 'showProperty']);
    Route::get('/getAllOfficeProperties/{Id}', [OfficeController::class, 'getAllOfficeProperties']);
});

//--------------------------Visitor-----------------------------------------------
Route::group(['prefix' => 'visitor'], function () {
    Route::get('/getRecentOffers', [PropertyController::class, 'getRecentOffers']);
    Route::get('/getAllproperty', [PropertyController::class, 'getAllproperty']);
    Route::get('/getPropertyVideos', [PropertyController::class, 'getPropertyVideos']);
});

//----------------------------Admin-----------------------------------------------
Route::group(['middleware' => ['jwt.auth', 'admin'], 'prefix' => 'admin'], function () {
    Route::get('/rejectOfficeRequest/{id}', [AdminController::class, 'rejectOfficeRequest']);
    Route::get('/approveOfficeRequest/{id}', [AdminController::class, 'approveOfficeRequest']);

    Route::post('/offices/{id}', [AdminController::class, 'rejectOffice']);

    Route::get('/pendingSubscription', [AdminController::class, 'pendingSubscription']);
    Route::get('/rejectSubscription/{id}', [AdminController::class, 'rejectSubscription']);

    Route::get('/pandingRequest', [AdminController::class, 'pandingRequest']);
    Route::get('/approveProperty/{id}', [AdminController::class, 'approveProperty']);
    Route::get('/rejectProperty/{id}', [AdminController::class, 'rejectProperty']);

    Route::get('/approveSubscription/{id}', [AdminController::class, 'approveSubscription']);
    Route::get('getOfficesByViews', [AdminController::class, 'getOfficesByViews']);
    Route::get('getOfficesByFollowers', [AdminController::class, 'getOfficesByFollowers']);
});
