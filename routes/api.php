<?php

use App\Http\Controllers\ProxyController;
use App\Http\Controllers\TelegramController;
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

Route::any('/telegram/bot',[TelegramController::class,'botWebhook'])->name('telegram.bot.webhook');


Route::prefix('/proxy')->middleware(['verify.user.token'])->group(function () {
    Route::get('/clash', [ProxyController::class, 'clashConfig'])->name('api.proxy.clash.config');
});

Route::prefix('/user')->middleware(['verify.user.token'])->group(function () {
    Route::get('/register/server',[ServerController::class,'registerUrl'])->name('api.user.server.register.url');
});

Route::prefix('/server')->middleware(['verify.server.token'])->group(function () {
    Route::get('/{server}/xray-server', [ProxyController::class, 'generateXrayServerConfig'])->name('api.server.xray.config');
    Route::get('/{server}/xray-access', [ProxyController::class, 'generateXrayAccessConfig'])->name('api.server.xray.config.access');
    Route::get('/{server}/bash',[ServerController::class,'bash'])->name('api.server.bash');
    Route::get('/{server}/cert',[ServerController::class,'cert'])->name('api.server.cert');
    Route::get('/{server}/cert/key',[ServerController::class,'certKey'])->name('api.server.cert.key');
});
Route::get('/server/register',[ServerController::class,'register'])->name('api.server.register');
