<?php

use App\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.token')->group(function () {
    Route::prefix('tasks')->group(function () {
        Route::post('/', [TaskController::class, 'store'])->name('tasks.store');
        Route::get('/{task}', [TaskController::class, 'show'])->name('tasks.show');
        Route::get('/', [TaskController::class, 'index'])->name('tasks.index');
    });
});