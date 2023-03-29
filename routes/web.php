<?php

use App\Http\Controllers\IndexController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProxyController;
use App\Http\Controllers\TelegramController;
use App\Http\Controllers\ServerController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', [IndexController::class,'index']);
Route::get('/ca',[IndexController::class,'ca'])->name('ca');


Route::get('/dashboard', [IndexController::class,'dashboard'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/servers',[ServerController::class,'index'])->name('servers.index');
    Route::patch('/servers/{server}',[ServerController::class,'update'])->name('servers.update');
    Route::delete('/servers/{server}',[ServerController::class,'destroy'])->name('servers.destroy');

    Route::delete('/servers/{server}/proxy/delete',[ServerController::class,'destroyProxy'])->name('servers.proxy.destroy');
    Route::delete('/servers/{server}/access/delete',[ServerController::class,'destroyAccess'])->name('servers.access.destroy');

    Route::get('/access',[ProxyController::class,'access'])->name('access.index');
    Route::post('/access',[ProxyController::class,'storeAccess'])->name('access.store');
    Route::patch('/access/{access}',[ProxyController::class,'updateAccess'])->name('access.update');
    Route::delete('/access/{access}',[ProxyController::class,'destroyAccess'])->name('access.destroy');

    Route::get('/proxies',[ProxyController::class,'index'])->name('proxies.index');
    Route::patch('/proxies/{proxy}',[ProxyController::class,'update'])->name('proxies.update');
    Route::delete('/proxies/{proxy}',[ProxyController::class,'destroy'])->name('proxies.destroy');
});

require __DIR__.'/auth.php';
