<?php

use App\Http\Controllers\Api\AnalysisCaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request)
{
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('cases')->middleware('throttle:30,1')->group(function ()
{
    Route::post('/', [AnalysisCaseController::class, 'store']);
    Route::get('{token}', [AnalysisCaseController::class, 'show'])->where('token', '[a-f0-9]{64}');
    Route::post('{token}/documents', [AnalysisCaseController::class, 'storeDocuments'])->where('token', '[a-f0-9]{64}');
});
