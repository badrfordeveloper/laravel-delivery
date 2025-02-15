<?php

use App\Models\Ramassage;
use Illuminate\Http\Request;
use App\Http\Middleware\IsActive;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ColisController;
use App\Http\Controllers\TarifController;
use App\Http\Controllers\RamassageController;

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

Route::group(['prefix' => 'auth'], function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);

    Route::group(['middleware' => 'auth:sanctum'], function() {
      Route::get('logout', [AuthController::class, 'logout']);
      Route::get('user', [AuthController::class, 'user']);
    });
});



Route::middleware(['auth:sanctum',IsActive::class])->group(function () {
    Route::apiResource('roles', RoleController::class);
    Route::get('permissions', [RoleController::class,'permissions']);
    Route::get('rolesList', [RoleController::class,'rolesList']);
    Route::apiResource('users', UserController::class);
    Route::apiResource('tarifs', TarifController::class);
    Route::apiResource('colis', ColisController::class);
    Route::apiResource('ramassage', RamassageController::class);
    Route::get('colisForRamassage', [RamassageController::class,'colisForRamassage']);
    Route::post('updateStatutRamassage', [RamassageController::class,'updateStatutRamassage']);
    Route::post('updateRamasseur', [RamassageController::class,'updateRamasseur']);
    Route::get('ramasseurs', [UserController::class,'ramasseurs']);



});

