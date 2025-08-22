<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\IssuesController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the RouteServiceProvider within a group
| assigned the "api" middleware group.
|
*/

Route::get('/issues', [IssuesController::class, 'index']);
Route::post('/issues', [IssuesController::class, 'store']);
Route::get('/issues/{id}', [IssuesController::class, 'show']);
Route::put('/issues/{id}', [IssuesController::class, 'update']);
Route::delete('/issues/{id}', [IssuesController::class, 'destroy']);
