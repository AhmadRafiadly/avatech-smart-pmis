<?php

use App\Http\Controllers\AuditController;
use App\Http\Controllers\AiMonitorController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExecutiveController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SystemHealthController;
use App\Http\Controllers\TeamController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

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

    Route::middleware('ceo.pm')->group(function () {
        Route::get('/executive', [ExecutiveController::class, 'index'])->name('executive.index');
        Route::get('/executive/insights', [ExecutiveController::class, 'insights'])->name('executive.insights');
        Route::get('/ai-monitor', [AiMonitorController::class, 'index'])->name('ai-monitor.index');
        Route::get('/system-health', [SystemHealthController::class, 'index'])->name('system-health.index');
        Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
        Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');
        Route::put('/clients/{client}', [ClientController::class, 'update'])->name('clients.update');
        Route::patch('/clients/{client}/archive', [ClientController::class, 'archive'])->name('clients.archive');
        Route::patch('/clients/{client}/restore', [ClientController::class, 'restore'])->name('clients.restore');
        Route::post('/clients/{client}/draft/whatsapp', [ClientController::class, 'draftWhatsapp'])->name('clients.draft.whatsapp');
        Route::post('/clients/{client}/draft/email', [ClientController::class, 'draftEmail'])->name('clients.draft.email');
        Route::get('/team', [TeamController::class, 'index'])->name('team.index');
        Route::post('/team/members', [TeamController::class, 'store'])->name('team.members.store');
        Route::put('/team/members/{member}', [TeamController::class, 'update'])->name('team.members.update');
        Route::patch('/team/members/{member}/archive', [TeamController::class, 'archive'])->name('team.members.archive');
        Route::patch('/team/members/{member}/restore', [TeamController::class, 'restore'])->name('team.members.restore');
        Route::post('/team/members/{member}/assignments', [TeamController::class, 'storeAssignment'])->name('team.assignments.store');
        Route::put('/team/members/{member}/assignments/{assignment}', [TeamController::class, 'updateAssignment'])->name('team.assignments.update');
        Route::delete('/team/members/{member}/assignments/{assignment}', [TeamController::class, 'destroyAssignment'])->name('team.assignments.destroy');
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings/preferences', [SettingsController::class, 'updatePreferences'])->name('settings.preferences.update');
        Route::post('/settings/integrations/{provider}/toggle', [SettingsController::class, 'toggleIntegration'])->name('settings.integrations.toggle');
        Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password.update');
        Route::post('/settings/recovery-codes', [SettingsController::class, 'regenerateRecoveryCodes'])->name('settings.recovery-codes.regenerate');
        Route::delete('/settings/sessions/{session}', [SettingsController::class, 'destroySession'])->name('settings.sessions.destroy');
        Route::post('/settings/account-deletion', [SettingsController::class, 'requestAccountDeletion'])->name('settings.account-deletion.request');
        Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
        Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
        Route::patch('/projects/{project}/archive', [ProjectController::class, 'archive'])->name('projects.archive');
        Route::patch('/projects/{project}/restore', [ProjectController::class, 'restore'])->name('projects.restore');
        Route::post('/projects/{project}/quick-assign', [ProjectController::class, 'quickAssignTeam'])->name('projects.quick-assign');
    });

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::post('/projects/{project}/modules', [ProjectController::class, 'storeModule'])->name('projects.modules.store');
    Route::put('/projects/{project}/modules/{module}', [ProjectController::class, 'updateModule'])->name('projects.modules.update');
    Route::delete('/projects/{project}/modules/{module}', [ProjectController::class, 'destroyModule'])->name('projects.modules.destroy');
    Route::post('/projects/{project}/tasks', [ProjectController::class, 'storeTask'])->name('projects.tasks.store');
    Route::put('/projects/{project}/tasks/{task}', [ProjectController::class, 'updateTask'])->name('projects.tasks.update');
    Route::delete('/projects/{project}/tasks/{task}', [ProjectController::class, 'destroyTask'])->name('projects.tasks.destroy');
    Route::patch('/projects/{project}/tasks/{task}/status', [ProjectController::class, 'updateTaskStatus'])->name('projects.tasks.status');
    Route::patch('/projects/{project}/tasks/{task}/design-deliverable', [ProjectController::class, 'updateDesignDeliverable'])->name('projects.tasks.design-deliverable');
    Route::post('/projects/{project}/tasks/{task}/design-deliverables', [ProjectController::class, 'storeDesignDeliverable'])->name('projects.tasks.design-deliverables.store');
    Route::put('/projects/{project}/tasks/{task}/design-deliverables/{deliverable}', [ProjectController::class, 'updateDesignDeliverableRow'])->name('projects.tasks.design-deliverables.update');
    Route::delete('/projects/{project}/tasks/{task}/design-deliverables/{deliverable}', [ProjectController::class, 'destroyDesignDeliverable'])->name('projects.tasks.design-deliverables.destroy');
    Route::get('/projects/{project}/tasks/{task}/design-deliverables/{deliverable}/preview', [ProjectController::class, 'previewDesignDeliverable'])->name('projects.tasks.design-deliverables.preview');
    Route::get('/projects/{project}/tasks/{task}/design-deliverables/{deliverable}/download', [ProjectController::class, 'downloadDesignDeliverable'])->name('projects.tasks.design-deliverables.download');
    Route::post('/projects/{project}/moms', [ProjectController::class, 'storeMom'])->name('projects.moms.store');
    Route::patch('/projects/{project}/moms/{mom}/summary', [ProjectController::class, 'updateMomSummary'])->name('projects.moms.summary');
    Route::post('/projects/{project}/ai-mom/fix', [ProjectController::class, 'fixLatestMom'])->name('projects.ai-mom.fix');
    Route::post('/projects/{project}/ai-mom/apply', [ProjectController::class, 'applyMomFix'])->name('projects.ai-mom.apply');
    Route::post('/projects/{project}/ai-wbs/generate', [ProjectController::class, 'generateWbsFromMom'])->name('projects.ai-wbs.generate');
    Route::post('/projects/{project}/ai-wbs/apply', [ProjectController::class, 'applyWbs'])->name('projects.ai-wbs.apply');
    Route::post('/projects/{project}/ai-test-cases/generate', [ProjectController::class, 'generateTestCases'])->name('projects.ai-test-cases.generate');
    Route::post('/projects/{project}/ai-test-cases/apply', [ProjectController::class, 'applyTestCases'])->name('projects.ai-test-cases.apply');
    Route::post('/projects/{project}/qc', [ProjectController::class, 'storeQcTest'])->name('projects.qc.store');
    Route::patch('/projects/{project}/qc/{qc}', [ProjectController::class, 'updateQcTest'])->name('projects.qc.update');
    Route::put('/projects/{project}/qc/{qc}', [ProjectController::class, 'editQcTest'])->name('projects.qc.edit');
    Route::delete('/projects/{project}/qc/{qc}', [ProjectController::class, 'destroyQcTest'])->name('projects.qc.destroy');
    Route::post('/projects/{project}/requirement-intake', [ProjectController::class, 'storeRequirementIntake'])->name('projects.requirement-intake.store');
    Route::put('/projects/{project}/requirement-intake/{intake}', [ProjectController::class, 'updateRequirementIntake'])->name('projects.requirement-intake.update');
    Route::delete('/projects/{project}/requirement-intake/{intake}', [ProjectController::class, 'destroyRequirementIntake'])->name('projects.requirement-intake.destroy');
    Route::get('/projects/{project}/export/wbs.pdf',       [ProjectController::class, 'exportWbsPdf'])->name('projects.export.wbs');
    Route::get('/projects/{project}/export/test-cases.pdf',[ProjectController::class, 'exportTestCasesPdf'])->name('projects.export.test-cases');
    Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::get('/audit', [AuditController::class, 'index'])->name('audit.index');
    Route::get('/audit/export.csv', [AuditController::class, 'exportCsv'])->name('audit.export');
    Route::get('/audit/report', [AuditController::class, 'report'])->name('audit.report');
});
