<?php

use Illuminate\Support\Facades\Route;
use App\Presentation\Http\Controller\AuthController;
use App\Presentation\Http\Controller\ProductController;
use App\Presentation\Http\Controller\CategoryController;
use App\Presentation\Http\Controller\SaleController;
use App\Presentation\Http\Controller\ReportController;
use App\Presentation\Http\Controller\HealthController;
use App\Presentation\Http\Controller\MediaController;

/*
|--------------------------------------------------------------------------
| API Routes — Simple Stock Flow (15 endpoints E-01 a E-15)
|--------------------------------------------------------------------------
*/

// Rutas Públicas (Anónimas)
Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/health', [HealthController::class, 'check']);
Route::get('/media/{key}', [MediaController::class, 'show']);

// Rutas Autenticadas (Cualquier rol: admin o seller)
Route::middleware(['auth.jwt'])->group(function () {
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{id}', [ProductController::class, 'show']);

    Route::post('/sales', [SaleController::class, 'store']);
    Route::get('/sales', [SaleController::class, 'index']);
    Route::get('/sales/{id}', [SaleController::class, 'show']);

    Route::get('/reports/sales', [ReportController::class, 'sales']);

    // Rutas protegidas solo para rol Admin (DP-04, P-38)
    Route::middleware(['role:admin'])->group(function () {
        Route::post('/auth/register', [AuthController::class, 'register']);
        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{id}', [ProductController::class, 'update']);
        Route::delete('/products/{id}', [ProductController::class, 'destroy']);
        Route::post('/products/{id}/image', [ProductController::class, 'uploadImage']);
    });
});
