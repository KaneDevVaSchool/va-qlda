<?php

use App\Http\Controllers\Auth\GoogleOAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/auth/google/callback', [GoogleOAuthController::class, 'callback']);

Route::view('/{any?}', 'app')->where('any', '.*');
