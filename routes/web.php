<?php

use Illuminate\Support\Facades\Route;
use App\Presentation\Http\Controller\HealthController;
use App\Presentation\Http\Controller\MediaController;

Route::get('/health', [HealthController::class, 'check']);
Route::get('/media/{key}', [MediaController::class, 'show']);
