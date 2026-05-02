<?php

use Illuminate\Support\Facades\Route;
use Seat\MumbleConnector\Http\Controllers\MumbleAdminController;
use Seat\MumbleConnector\Http\Controllers\MumbleUserController;

Route::group([
    'namespace'  => 'Seat\MumbleConnector\Http\Controllers',
    'prefix'     => 'mumble-admin',
    'middleware' => ['web', 'auth', 'can:global.superuser'],
], function () {
    // Admin Dashboard
    Route::get('/', [MumbleAdminController::class, 'index'])
        ->name('mumble::admin.index');

    // Settings
    Route::get('/settings', [MumbleAdminController::class, 'settings'])
        ->name('mumble::admin.settings');
    Route::post('/settings', [MumbleAdminController::class, 'updateSettings'])
        ->name('mumble::admin.settings.update');

    // Group Mappings
    Route::get('/groups', [MumbleAdminController::class, 'groups'])
        ->name('mumble::admin.groups');
    Route::post('/groups', [MumbleAdminController::class, 'addGroupMapping'])
        ->name('mumble::admin.groups.add');
    Route::delete('/groups/{id}', [MumbleAdminController::class, 'deleteGroupMapping'])
        ->name('mumble::admin.groups.delete');

    // Force-register a SeAT user bypassing the whitelist (for returning members with stale ESI)
    Route::post('/users/force-register', [MumbleAdminController::class, 'forceRegisterUser'])
        ->name('mumble::admin.users.force-register');

    // Users Management
    Route::get('/users', [MumbleAdminController::class, 'users'])
        ->name('mumble::admin.users');
    Route::post('/users/sync', [MumbleAdminController::class, 'syncAllUsers'])
        ->name('mumble::admin.users.sync');
    Route::post('/users/{id}/sync', [MumbleAdminController::class, 'syncUser'])
        ->name('mumble::admin.users.sync.single');
    Route::delete('/users/{id}', [MumbleAdminController::class, 'removeUser'])
        ->name('mumble::admin.users.remove');

    // Logs
    Route::get('/logs', [MumbleAdminController::class, 'logs'])
        ->name('mumble::admin.logs');

    // Test Connection
    Route::post('/test-connection', [MumbleAdminController::class, 'testConnection'])
        ->name('mumble::admin.test');

    // Temporary Links
    Route::get('/links', [MumbleAdminController::class, 'temporaryLinks'])
        ->name('mumble::admin.links');
    Route::post('/links', [MumbleAdminController::class, 'createTemporaryLink'])
        ->name('mumble::admin.links.add');
    Route::delete('/links/{id}', [MumbleAdminController::class, 'deleteTemporaryLink'])
        ->name('mumble::admin.links.delete');
});

// User-facing routes
Route::group([
    'namespace' => 'Seat\MumbleConnector\Http\Controllers',
    'prefix' => 'mumble',
    'middleware' => ['web', 'auth'],
], function () {
    // User's own Mumble profile
    Route::get('/profile', [MumbleUserController::class, 'profile'])
        ->name('mumble::user.profile');
    
    // Register Mumble account
    Route::post('/register', [MumbleUserController::class, 'register'])
        ->name('mumble::user.register');
    
    // Reset password
    Route::post('/reset-password', [MumbleUserController::class, 'resetPassword'])
        ->name('mumble::user.reset-password');

    // Re-sync to Mumble server (when bridge was down on first registration)
    Route::post('/sync', [MumbleUserController::class, 'syncToServer'])
        ->name('mumble::user.sync');
});

// Guest routes (publicly accessible via token)
Route::group([
    'namespace' => 'Seat\MumbleConnector\Http\Controllers',
    'prefix' => 'mumble/guest',
    'middleware' => ['web'],
], function () {
    Route::get('/{token}', [MumbleUserController::class, 'guestLink'])
        ->name('mumble::guest.link');
});
