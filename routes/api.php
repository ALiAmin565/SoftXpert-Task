<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TaskController;
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

// Authentication routes
Route::group(['prefix' => 'auth'], function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    
    // Protected auth routes
    Route::middleware('auth:api')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);
    });
});

// Task management routes
Route::middleware('auth:api')->group(function () {
    // Standard CRUD operations
    Route::apiResource('tasks', TaskController::class);
    
    // Task dependency management routes
    Route::post('tasks/{task}/dependencies', [TaskController::class, 'addDependencies']);
    Route::delete('tasks/{task}/dependencies', [TaskController::class, 'removeDependencies']);
    Route::delete('tasks/{task}/dependencies/all', [TaskController::class, 'removeAllDependencies']);
    Route::get('tasks/{task}/dependency-chain', [TaskController::class, 'getDependencyChain']);
});

// Health check route
Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'Task Management API is running',
        'timestamp' => now()
    ]);
});
