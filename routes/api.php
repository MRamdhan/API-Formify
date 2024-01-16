<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FormController;
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

Route::prefix('v1')->group(function(){
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::post('auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('forms', [FormController::class,'createForm']);
        Route::get('forms', [FormController::class,'getForms']);
        Route::get('forms/{form_slug}', [FormController::class,'formSlug']);
        Route::post('forms/{form_slug}/questions', [FormController::class,'addquestions']);
        Route::delete('forms/{form_slug}/questions/{question_id}', [FormController::class,'removequestions']);
        Route::post('forms/{form_slug}/responses', [FormController::class,'responses']);
        Route::get('forms/{form_slug}/responses', [FormController::class,'getResponses']);
    });
});
