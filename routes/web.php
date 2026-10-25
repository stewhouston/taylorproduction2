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
    /*Route::get('/dashboard', function () {
        // return view('dashboard');
        return redirect('/estimates');
    })->name('dashboard');*/

    Route::get('/', [Estimates::class, 'index'])->name('estimates.index');
    Route::get('/estimates', [Estimates::class, 'index'])->name('estimates.index');
    Route::get('/estimates/archives', [Estimates::class, 'archives'])->name('estimates.archives');
    Route::get('/estimate/create', [Estimates::class, 'create'])->name('estimate.create');
    Route::get('/estimate/{id}', [Estimates::class, 'show'])->name('estimate.show');
    Route::get('/estimate/{id}/edit', [Estimates::class, 'show'])->name('estimate.edit');
    Route::get('/estimate/{id}/destroy', [Estimates::class, 'show'])->name('estimate.destroy');
    Route::get('/estimate/{id}/print', [Estimates::class, 'show'])->name('estimate.print');
    Route::post('/estimates/{id}/set_archive_state', [Estimates::class])->name('ajaxSetArchiveState');

    Route::get('/shipping/estimate/{id}', [Estimate::class, 'show'])->name('shipping.drops');
});
