<?php

use App\Http\Controllers\CasePageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CasePageController::class, 'home'])->name('cases.home');
Route::post('/cases', [CasePageController::class, 'store'])->name('cases.store');
Route::get('/cases/{token}', [CasePageController::class, 'show'])->where('token', '[a-f0-9]{64}')->name('cases.show');
Route::post('/cases/{token}/documents', [CasePageController::class, 'storeDocuments'])->where('token', '[a-f0-9]{64}')->name('cases.documents.store');
