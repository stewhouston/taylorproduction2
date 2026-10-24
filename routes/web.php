<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Estimates;

Route::get('/', function () {
    return redirect('/login');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        // return view('dashboard');
        return redirect('/estimates');
    })->name('dashboard');

    Route::get('/estimates', [Estimates::class, 'index'])->name('estimates.index');
    Route::get('/estimates/create', [Estimates::class, 'create'])->name('estimates.create');
    Route::get('/estimate/{id}', [Estimates::class, 'index'])->name('estimate.show');
});
