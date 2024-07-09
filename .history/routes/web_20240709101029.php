<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/mpesa/initiate', [MpesaController::class, 'initiate']);
