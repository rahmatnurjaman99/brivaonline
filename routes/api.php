<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use RahmatNurjaman99\BrivaOnline\Http\Controllers\BrivaController;

Route::prefix('snap/v1.0')->group(function () {
    Route::post('/access-token/b2b', [BrivaController::class, 'accessToken'])->name('access-token.b2b');
    Route::post('/transfer-va/inquiry', [BrivaController::class, 'inquiry'])->name('transfer-va.inquiry');
    Route::post('/transfer-va/payment', [BrivaController::class, 'payment'])->name('transfer-va.payment');
})->as('snap.v1.0.');
