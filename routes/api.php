<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use RahmatNurjaman99\BrivaOnline\Http\Controllers\BrivaController;

Route::post('/snap/v1.0/access-token/b2b', [BrivaController::class, 'accessToken']);
Route::post('/v1.0/transfer-va/inquiry', [BrivaController::class, 'inquiry']);
Route::post('/v1.0/transfer-va/payment', [BrivaController::class, 'payment']);
