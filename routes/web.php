<?php

use App\Http\Controllers\AircraftController;
use App\Http\Controllers\AircraftTypeController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FlightController;
use App\Http\Controllers\FlightFileController;
use App\Http\Controllers\FlightGroupController;
use App\Http\Controllers\PresentationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Support\Permission as P;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web routes
|--------------------------------------------------------------------------
| Run `php artisan route:list` to see every URL with its controller.
| Permission keys live in App\Support\Permission.
*/

// IDs are numeric, so "/flights/create" never matches "/flights/{flight}".
Route::pattern('flight', '[0-9]+');
Route::pattern('group', '[0-9]+');
Route::pattern('aircraft', '[0-9]+');
Route::pattern('aircraftType', '[0-9]+');
Route::pattern('user', '[0-9]+');
Route::pattern('role', '[0-9]+');

Route::view('/', 'welcome')->name('home');

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->middleware('throttle:10,1');
    Route::view('forgot-password', 'auth.forgot-password')->name('password.request');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('dashboard', DashboardController::class)->name('dashboard')->middleware('can:'.P::DASHBOARD_VIEW);

    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [ProfileController::class, 'password'])->name('profile.password');

    // ---- Flights ------------------------------------------------------------
    Route::middleware('can:'.P::FLIGHTS_MANAGE)->group(function () {
        Route::resource('flights', FlightController::class)->except(['index', 'show']);
        Route::post('flights/{flight}/reprocess', [FlightFileController::class, 'reprocess'])->name('flights.reprocess');
        Route::delete('flights/{flight}/bvr', [FlightFileController::class, 'destroyBvr'])->name('flights.bvr.destroy');
    });

    Route::middleware('can:'.P::FLIGHTS_VIEW)->group(function () {
        Route::get('flights', [FlightController::class, 'index'])->name('flights.index');
        Route::get('flights/{flight}', [FlightController::class, 'show'])->name('flights.show');
        Route::get('flights/{flight}/present', [PresentationController::class, 'flight'])->name('flights.present');
        Route::get('flights/{flight}/status', [FlightFileController::class, 'status'])->name('flights.status');
        Route::get('flights/{flight}/telemetry', [FlightFileController::class, 'telemetry'])->name('flights.telemetry');
        Route::get('flights/{flight}/fdr', [FlightFileController::class, 'fdr'])->name('flights.fdr');
        Route::get('flights/{flight}/bvr', [FlightFileController::class, 'bvr'])->name('flights.bvr');
    });

    // ---- Groups / formations --------------------------------------------------
    Route::middleware('can:'.P::GROUPS_MANAGE)->group(function () {
        Route::resource('groups', FlightGroupController::class)->except(['index', 'show'])->parameters(['groups' => 'group']);
    });

    Route::middleware('can:'.P::GROUPS_VIEW)->group(function () {
        Route::get('groups', [FlightGroupController::class, 'index'])->name('groups.index');
        Route::get('groups/{group}', [FlightGroupController::class, 'show'])->name('groups.show');
        Route::get('groups/{group}/present', [PresentationController::class, 'group'])->name('groups.present');
    });

    // ---- Aircraft & aircraft types -------------------------------------------------
    Route::middleware('can:'.P::AIRCRAFT_MANAGE)->group(function () {
        Route::resource('aircrafts', AircraftController::class)->except(['index', 'show'])
            ->names('aircraft')->parameters(['aircrafts' => 'aircraft']);
        Route::resource('aircraft-types', AircraftTypeController::class)->except(['index', 'show'])
            ->parameters(['aircraft-types' => 'aircraftType']);
    });

    Route::middleware('can:'.P::AIRCRAFT_VIEW)->group(function () {
        Route::get('aircrafts', [AircraftController::class, 'index'])->name('aircraft.index');
        Route::get('aircrafts/{aircraft}', [AircraftController::class, 'show'])->name('aircraft.show');
        Route::get('aircraft-types', [AircraftTypeController::class, 'index'])->name('aircraft-types.index');
        Route::get('aircraft-types/{aircraftType}', [AircraftTypeController::class, 'show'])->name('aircraft-types.show');
    });

    // 3D model file: needed by anyone who can present a flight.
    Route::get('aircraft-types/{aircraftType}/model', [AircraftTypeController::class, 'model'])->name('aircraft-types.model');

    // ---- Users & roles -------------------------------------------------------------
    Route::resource('users', UserController::class)->middleware('can:'.P::USERS_MANAGE);
    Route::resource('roles', RoleController::class)->except('show')->middleware('can:'.P::ROLES_MANAGE);
});
