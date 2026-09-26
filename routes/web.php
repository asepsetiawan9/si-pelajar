<?php

use App\Http\Controllers\LaporanDokumenController;
use App\Http\Controllers\LaporanExportController;
use App\Http\Controllers\LaporanPdfController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::middleware(['auth'])->group(function () {
    // PDF Downloads
    Route::get('/laporan/{laporan}/pdf', [LaporanPdfController::class, 'downloadKecamatanPdf'])
        ->name('spko.laporan.pdf');

    Route::get('/laporan-detail/{detail}/pdf', [LaporanPdfController::class, 'downloadDetailPdf'])
        ->name('spko.laporan-detail.pdf');

    // Excel Downloads
    Route::get('/laporan/{laporan}/excel', [LaporanExportController::class, 'downloadKecamatanExcel'])
        ->name('spko.laporan.excel');

    Route::get('/laporan-detail/{detail}/excel', [LaporanExportController::class, 'downloadDetailExcel'])
        ->name('spko.laporan-detail.excel');

    // Secure Document Attachment Download
    Route::get('/laporan-dokumen/{dokumen}/download', [LaporanDokumenController::class, 'download'])
        ->name('spko.dokumen.download');
});
