<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ServiceDiscoveryController;

Route::post('/service-discovery', [ServiceDiscoveryController::class, 'discover']);

