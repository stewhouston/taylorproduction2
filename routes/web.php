<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Estimates;
use App\Http\Controllers\Documents;

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
    Route::post('/estimate/store', [Estimates::class, 'store'])->name('estimate.store');
    Route::get('/estimate/{id}', [Estimates::class, 'show'])->name('estimate.show');
    Route::get('/estimate/{id}/edit', [Estimates::class, 'edit'])->name('estimate.edit');
    Route::post('/estimate/{id}/update', [Estimates::class, 'update'])->name('estimate.update');
    // This should be DELETE, use @csrf and @method('DELETE') when time permits
    Route::get('/estimate/{id}/destroy', [Estimates::class, 'destroy'])->name('estimate.destroy');
    Route::get('/estimate/{id}/print', [Estimates::class, 'print'])->name('estimate.print');
    Route::post('/estimate/{id}/set_archive_state', [Estimates::class, 'ajaxSetArchiveState'])->name('estimate.ajaxSetArchiveState');
    Route::post('/estimate/{id}/set_rush_fee', [Estimates::class, 'ajaxSetRushFee'])->name('estimate.ajaxSetRushFee');
    Route::post('/estimate/{id}/set_overrides', [Estimates::class, 'ajaxSetOverrides'])->name('estimate.ajaxSetOverrides');
    Route::post('/estimate/{id}/set_alerts', [Estimates::class, 'ajaxSetAlerts'])->name('estimate.ajaxSetAlerts');
    Route::post('/estimate/{id}/set_as_print_order', [Estimates::class, 'ajaxSetAsPrintOrder'])->name('estimate.ajaxSetAsPrintOrder');

    Route::post('/estimate/{id}/document/store', [Documents::class, 'store'])->name('estimate.document.store');
    Route::delete('/estimate/{id}/document/delete', [Documents::class, 'delete'])->name('estimate.document.delete');
    Route::get('/estimate/{id}/document/{document_id}/production_details', [Documents::class, 'productionDetails'])->name('estimate.document.production_details');
    foreach(['saddlestitch', 'digest-saddlestitch', 'folder', 'multi-sheet', 'perfect-bind', 'single-sheet', 'custom'] as $slug) {
        Route::get('/estimate/{id}/document/create/{type}', [Documents::class, 'create'])->name("estimates.documents.create.{$slug}");
    }

    Route::get('/shipping/estimate/{id}', [Estimates::class, 'show'])->name('shipping.drops');
});
