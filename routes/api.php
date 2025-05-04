<?php

use App\Models\Ramassage;
use Illuminate\Http\Request;
use App\Http\Middleware\IsActive;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ColisController;
use App\Http\Controllers\ZoneController;
use App\Http\Controllers\RetourController;
use App\Http\Controllers\RamassageController;
use App\Http\Controllers\FactureLivreurController;
use App\Http\Controllers\FactureVendeurController;
use App\Http\Controllers\FrontController;

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
Route::post('/front/mailContact', [FrontController::class, 'mailContact']);

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
    Route::get('ramasseurs', [UserController::class,'ramasseurs']);
    Route::get('vendeurs', [UserController::class,'vendeurs']);

    Route::apiResource('zones', ZoneController::class);
    Route::apiResource('colis', ColisController::class);
    Route::post('parametrerColis', [ColisController::class,'parametrerColis']);
    Route::post('parametrerGroupColis', [ColisController::class,'parametrerGroupColis']);
    Route::post('updateStatutColis', [ColisController::class,'updateStatutColis']);

    Route::apiResource('ramassage', RamassageController::class);
    Route::get('colisForRamassage', [RamassageController::class,'colisForRamassage']);
    Route::post('updateStatutRamassage', [RamassageController::class,'updateStatutRamassage']);
    Route::post('parametrerRamassage', [RamassageController::class,'parametrerRamassage']);

    Route::post('scannerEntrepot', [RamassageController::class,'scannerEntrepot']);

    Route::post('scannerRetourEntrepot', [ColisController::class,'scannerRetourEntrepot']);

    Route::post('scannerPreparer', [RetourController::class,'scannerPreparer']);

    Route::apiResource('retour', RetourController::class);
    Route::get('colisCanRetour', [RetourController::class,'colisCanRetour']);
    Route::post('parametrerRetour', [RetourController::class,'parametrerRetour']);
    Route::post('updateStatutRetour', [RetourController::class,'updateStatutRetour']);


    Route::post('generateLivreurFactures', [FactureLivreurController::class,'generateLivreurFactures']);
    Route::apiResource('factureLivreur', FactureLivreurController::class);
    Route::post('updateStatutFactureLivreur', [FactureLivreurController::class,'updateStatutFactureLivreur']);

    Route::post('generateVendeurFactures', [FactureVendeurController::class,'generateVendeurFactures']);
    Route::apiResource('factureVendeur', FactureVendeurController::class);
    Route::post('updateStatutFactureVendeur', [FactureVendeurController::class,'updateStatutFactureVendeur']);




});

