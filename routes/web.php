<?php

use App\Http\Controllers\AuditController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExecutiveController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TeamController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/*
 * Normal Smart-PMIS login (CEO/PM + operational roles). Admin-tier users
 * (admin / super_admin / developer) still use /admin/login (Filament panel).
 * The route name 'login' is preserved so Laravel's auth middleware and any
 * `route('login')` references keep working.
 */
Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'store'])->name('login.store');

Route::middleware('auth')->group(function () {
    /*
     * Normal Smart-PMIS logout — used by CEO/PM and operational users
     * whose roles are blocked from /admin (Filament panel). Keep this
     * separate from filament.admin.auth.logout so non-admin sessions
     * never hit the panel's role gate and get 403.
     */
    Route::post('/logout', function (\Illuminate\Http\Request $request) {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    })->name('logout');

    Route::get('/executive', [ExecutiveController::class, 'index'])->name('executive.index');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::patch('/projects/{project}/archive', [ProjectController::class, 'archive'])->name('projects.archive');
    Route::patch('/projects/{project}/restore', [ProjectController::class, 'restore'])->name('projects.restore');
    Route::post('/projects/{project}/modules', [ProjectController::class, 'storeModule'])->name('projects.modules.store');
    Route::post('/projects/{project}/tasks', [ProjectController::class, 'storeTask'])->name('projects.tasks.store');
    Route::patch('/projects/{project}/tasks/{task}/status', [ProjectController::class, 'updateTaskStatus'])->name('projects.tasks.status');
    Route::post('/projects/{project}/moms', [ProjectController::class, 'storeMom'])->name('projects.moms.store');
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
    Route::get('/audit', [AuditController::class, 'index'])->name('audit.index');
    Route::get('/audit/export.csv', [AuditController::class, 'exportCsv'])->name('audit.export');
    Route::get('/audit/report', [AuditController::class, 'report'])->name('audit.report');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings/preferences', [SettingsController::class, 'updatePreferences'])->name('settings.preferences.update');
    Route::post('/settings/integrations/{provider}/toggle', [SettingsController::class, 'toggleIntegration'])->name('settings.integrations.toggle');
    Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password.update');
    Route::post('/settings/recovery-codes', [SettingsController::class, 'regenerateRecoveryCodes'])->name('settings.recovery-codes.regenerate');
    Route::delete('/settings/sessions/{session}', [SettingsController::class, 'destroySession'])->name('settings.sessions.destroy');
    Route::post('/settings/account-deletion', [SettingsController::class, 'requestAccountDeletion'])->name('settings.account-deletion.request');
});
