<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LeadController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Lead capture webhook
Route::post('/leads/capture', [LeadController::class, 'capture']);

// Track lead activity
Route::post('/leads/track-activity', [LeadController::class, 'trackActivity']);

// Get lead details
Route::get('/leads/{id}', [LeadController::class, 'show']);

// Dashboard stats
Route::get('/stats', [LeadController::class, 'stats']);