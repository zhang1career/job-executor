<?php

use App\Http\Controllers\XxlJobController;
use App\Http\Middleware\XxljobAuthentication;
use Illuminate\Support\Facades\Route;


Route::prefix("xxl-job")->middleware([XxljobAuthentication::class])->group(function () {
    Route::get('beat', [XxlJobController::class, 'beat']);
    Route::post('run', [XxlJobController::class, 'run']);
    Route::post('kill', [XxlJobController::class, 'kill']);
});
