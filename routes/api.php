<?php

use App\Http\Controllers\Api\MatchController;
use Illuminate\Support\Facades\Route;

Route::post('/matches', [MatchController::class, 'store']);
