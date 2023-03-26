<?php

use App\Http\Controllers\ProxyController;
use App\Http\Controllers\ServerController;
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
    Route::get('/clash', [ProxyController::class, 'clashConfig'])->name('api.proxy.clash.config');
});

Route::prefix('/user')->middleware(['verify.user.token'])->group(function () {
    Route::get('/register/server',[ServerController::class,'registerUrl'])->name('api.user.server.register.url');
});

Route::prefix('/server')->middleware(['verify.server.token'])->group(function () {
    Route::get('/{server}/xray-server', [ProxyController::class, 'generateXrayServerConfig'])->name('api.server.xray.config');
    Route::get('/{server}/xray-access', [ProxyController::class, 'generateXrayAccessConfig'])->name('api.server.xray.config.access');
});
Route::get('/server/register',[ServerController::class,'register'])->name('api.server.register');
