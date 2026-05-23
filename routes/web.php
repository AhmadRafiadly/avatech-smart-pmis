<?php

use App\Http\Controllers\ExecutiveController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::redirect('/login', '/admin/login')->name('login');

Route::middleware('auth')->group(function () {
    Route::get('/executive', [ExecutiveController::class, 'index'])->name('executive.index');
    Route::view('/dashboard', 'placeholders.coming-soon', ['title' => 'Dashboard'])->name('dashboard.index');
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::view('/clients',   'clients.index', ['title' => 'Client Directory'])->name('clients.index');
    Route::view('/team',      'team.index', ['title' => 'Team Management'])->name('team.index');
    Route::view('/audit',     'audit.index', ['title' => 'Audit Trail'])->name('audit.index');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings/preferences', [SettingsController::class, 'updatePreferences'])->name('settings.preferences.update');
    Route::post('/settings/integrations/{provider}/toggle', [SettingsController::class, 'toggleIntegration'])->name('settings.integrations.toggle');
    Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password.update');
    Route::post('/settings/recovery-codes', [SettingsController::class, 'regenerateRecoveryCodes'])->name('settings.recovery-codes.regenerate');
    Route::delete('/settings/sessions/{session}', [SettingsController::class, 'destroySession'])->name('settings.sessions.destroy');
    Route::post('/settings/account-deletion', [SettingsController::class, 'requestAccountDeletion'])->name('settings.account-deletion.request');
});
