<?php

use App\Http\Controllers\BukuController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\PeminjamanController;
use Illuminate\Support\Facades\Route;

Route::apiResource('kategori', KategoriController::class);
Route::apiResource('buku', BukuController::class);

Route::get('peminjaman', [PeminjamanController::class, 'index']);
Route::post('peminjaman', [PeminjamanController::class, 'store']);
Route::patch('peminjaman/{id}/kembalikan', [PeminjamanController::class, 'kembalikan'])->whereNumber('id');
