<?php

use App\Http\Controllers\ApiPublic\ConteneurPublicController;
use App\Http\Controllers\ApiPublic\EnlevementPublicController;
use App\Http\Controllers\ApiPublic\ProductionPublicController;
use App\Http\Controllers\ApiPublic\RegistrePublicController;
use App\Http\Controllers\ApiPublic\TypeDechetPublicController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Publique v1
|--------------------------------------------------------------------------
| Prefix: /api/v1
| Middleware: auth:sanctum + ApiPublicThrottle (1000 req/h)
| Documentation: /api/v1/docs (future Swagger/OpenAPI)
*/

// Types de dechets
Route::get('/types-dechets', [TypeDechetPublicController::class, 'index']);
Route::get('/types-dechets/{id}', [TypeDechetPublicController::class, 'show']);

// Conteneurs
Route::get('/conteneurs', [ConteneurPublicController::class, 'index']);
Route::get('/conteneurs/{id}', [ConteneurPublicController::class, 'show']);

// Productions
Route::get('/productions', [ProductionPublicController::class, 'index']);
Route::post('/productions', [ProductionPublicController::class, 'store']);
Route::post('/productions/batch', [ProductionPublicController::class, 'storeBatch']);
Route::post('/productions/suggest', [ProductionPublicController::class, 'suggest']);

// Enlevements
Route::get('/enlevements', [EnlevementPublicController::class, 'index']);
Route::get('/enlevements/{id}', [EnlevementPublicController::class, 'show']);

// Registre
Route::get('/registre', [RegistrePublicController::class, 'index']);
Route::get('/registre/export', [RegistrePublicController::class, 'export']);
