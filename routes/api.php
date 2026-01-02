<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\IpAddressController;
use App\Http\Controllers\AuditController;

// Route::post('/register', [AuthController::class, 'register']);
// Route::post('/login',    [AuthController::class, 'login']);
// Route::post('/refresh', [AuthController::class, 'refresh'])
//     ->middleware('auth:sanctum');


// Route::middleware('auth:sanctum')->group(function () {
//     Route::post('/logout', [AuthController::class, 'logout']);

//     Route::get('/ip-addresses', [IpAddressController::class, 'index']);
//     Route::post('/ip-addresses', [IpAddressController::class, 'store']);
//     Route::put('/ip-addresses/{ip}', [IpAddressController::class, 'update']);
//     Route::delete('/ip-addresses/{ip}', [IpAddressController::class, 'destroy']);

//     Route::get('/audit/user/session', [AuditController::class, 'userSession']);
//     Route::get('/audit/user/lifetime', [AuditController::class, 'userLifetime']);
//     Route::get('/audit/ip/{id}/session', [AuditController::class, 'ipSession']);
//     Route::get('/audit/ip/{id}/lifetime', [AuditController::class, 'ipLifetime']);
// });



Route::post('/login', [AuthController::class, 'login']);
Route::post('/refresh', [AuthController::class, 'refresh']);

Route::middleware('jwt')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::resource('ip-addresses', IpAddressController::class);
    Route::get('/audit/user/session', [AuditController::class, 'userSession']);
});
