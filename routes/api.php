<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::prefix('/proxy')->middleware(['verify.user.token'])->group(function () {
    Route::get('/clash', [\App\Http\Controllers\ProxyController::class, 'clashConfig']);
});

Route::prefix('/user')->middleware(['verify.user.token'])->group(function () {
    Route::get('/register/server',[\App\Http\Controllers\ServerController::class,'registerUrl']);
});


Route::prefix('/server')->group(function () {
    Route::get('/{server}/xray', [\App\Http\Controllers\ProxyController::class, 'generateXrayServerConfig'])->name('api.server.xray.config');
    Route::get('/register',[\App\Http\Controllers\ServerController::class,'register'])->name('api.server.register');

});
