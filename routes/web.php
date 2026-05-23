<?php

use App\Http\Controllers\ExecutiveController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TeamController;
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
    Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::patch('/projects/{project}/archive', [ProjectController::class, 'archive'])->name('projects.archive');
    Route::patch('/projects/{project}/restore', [ProjectController::class, 'restore'])->name('projects.restore');
    Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
    Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');
    Route::put('/clients/{client}', [ClientController::class, 'update'])->name('clients.update');
    Route::patch('/clients/{client}/archive', [ClientController::class, 'archive'])->name('clients.archive');
    Route::patch('/clients/{client}/restore', [ClientController::class, 'restore'])->name('clients.restore');
    Route::get('/team', [TeamController::class, 'index'])->name('team.index');
    Route::post('/team/members', [TeamController::class, 'store'])->name('team.members.store');
    Route::put('/team/members/{member}', [TeamController::class, 'update'])->name('team.members.update');
    Route::patch('/team/members/{member}/archive', [TeamController::class, 'archive'])->name('team.members.archive');
    Route::patch('/team/members/{member}/restore', [TeamController::class, 'restore'])->name('team.members.restore');
    Route::post('/team/members/{member}/assignments', [TeamController::class, 'storeAssignment'])->name('team.assignments.store');
    Route::view('/audit',     'audit.index', ['title' => 'Audit Trail'])->name('audit.index');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings/preferences', [SettingsController::class, 'updatePreferences'])->name('settings.preferences.update');
    Route::post('/settings/integrations/{provider}/toggle', [SettingsController::class, 'toggleIntegration'])->name('settings.integrations.toggle');
    Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password.update');
    Route::post('/settings/recovery-codes', [SettingsController::class, 'regenerateRecoveryCodes'])->name('settings.recovery-codes.regenerate');
    Route::delete('/settings/sessions/{session}', [SettingsController::class, 'destroySession'])->name('settings.sessions.destroy');
    Route::post('/settings/account-deletion', [SettingsController::class, 'requestAccountDeletion'])->name('settings.account-deletion.request');
});
