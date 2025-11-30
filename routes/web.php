<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Incident\IncidentDashboardController;
use App\Http\Controllers\Incident\IncidentFollowUpController;
use App\Http\Controllers\GameScoreController;

// Root → Welcome page
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'auth' => [
            'user' => auth()->user(),
        ],
    ]);
})->name('welcome');

// Public About page
Route::get('/about', function () {
    return Inertia::render('About', [
        'auth' => [
            'user' => auth()->user(),
        ],
    ]);
})->name('about');

// Public Game page
Route::get('/orbital-dodge', function () {
    return Inertia::render('OrbitalDodge', [
        'auth' => [
            'user' => auth()->user(),
        ],
    ]);
})->name('orbital-dodge');

Route::get('/api/leaderboard/orbital-dodge', [GameScoreController::class, 'index']);
Route::post('/api/leaderboard/orbital-dodge', [GameScoreController::class, 'store']);

// Public Incident Command Center dashboard (read only)
Route::get('/incident-command', [IncidentDashboardController::class, 'index'])
    ->name('incident.dashboard');

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    // Optional separate login/register just for this demo
    Route::get('/incident-command/login', function () {
        return inertia('Incident/Login');
    })->name('incident.login');

    Route::get('/incident-command/register', function () {
        return inertia('Incident/Register');
    })->name('incident.register');
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Update incident status
    Route::patch(
        '/incident-command/incidents/{incident}',
        [IncidentDashboardController::class, 'update']
    )->name('incident.update');

    // Create follow up for an incident
    Route::post(
        '/incident-command/incidents/{incident}/follow-ups',
        [IncidentFollowUpController::class, 'store']
    )->name('incident.follow-up.store');

    // Update follow up status
    Route::patch(
        '/incident-command/follow-ups/{followUp}',
        [IncidentFollowUpController::class, 'update']
    )->name('incident.follow-up.update');

    Route::post('/incident-command/incidents', [IncidentDashboardController::class, 'store'])
        ->name('incident.store');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});


// API token
Route::get('/api-token', [AuthController::class, 'apiToken'])->name('api-token');
