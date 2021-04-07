<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthUserController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
*/
Route::post('signup', [UserController::class, 'register']);
Route::post('verify-account', [UserController::class, 'verifyAccount']);

Route::post('login', [AuthUserController::class, 'login']);



Route::post('testmail', [UserController::class, 'testMail']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
}); 

Route::middleware('auth:sanctum')->post('/profile', [UserController::class, 'updateProfile'] );

Route::middleware('auth:sanctum')->post('/send-invites', [UserController::class, 'sendInvites'] );

Route::get('test', function (Request $request) {
    return response()->json([
        'msg' => 'Hello World!'
    ]);
});

