<?php

use App\Http\Controllers\KategoriController;
use Illuminate\Support\Facades\Route;

Route::apiResource('kategori', KategoriController::class);
