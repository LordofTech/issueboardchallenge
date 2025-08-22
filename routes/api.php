<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\IssueController;

// Frontend route
Route::get('/', function () {
    return view('issues.index');
});

// API routes
Route::prefix('api')->group(function () {
    Route::get('/issues', [IssueController::class, 'index']);
    Route::post('/issues', [IssueController::class, 'store']);
    Route::patch('/issues/{issue}', [IssueController::class, 'update']);
});