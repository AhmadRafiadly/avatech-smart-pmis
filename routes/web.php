<?php

use App\Http\Controllers\ExecutiveController;
use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::redirect('/login', '/admin/login')->name('login');

Route::middleware('auth')->group(function () {
    Route::get('/executive', [ExecutiveController::class, 'index'])->name('executive.index');
    Route::view('/dashboard', 'placeholders.coming-soon', ['title' => 'Dashboard'])->name('dashboard.index');
    Route::view('/projects',  'projects.index', ['title' => 'Project Master'])->name('projects.index');
    Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::view('/clients',   'clients.index', ['title' => 'Client Directory'])->name('clients.index');
    Route::view('/team',      'team.index', ['title' => 'Team Management'])->name('team.index');
    Route::view('/audit',     'audit.index', ['title' => 'Audit Trail'])->name('audit.index');
    Route::view('/settings',  'settings.index', ['title' => 'Settings'])->name('settings.index');
});
