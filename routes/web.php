<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SubUserController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return redirect('/login');
});

Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::post('/login', function (Illuminate\Http\Request $request) {
    if (Auth::attempt($request->only('email', 'password'))) {
        return redirect('/dashboard');
    }
    return back()->withErrors(['email' => 'Invalid credentials']);
})->name('login.post');

Route::get('/register', function () {
    return view('auth.register');
})->name('register');

Route::post('/register', function (Illuminate\Http\Request $request) {
    $user = \App\Models\User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => bcrypt($request->password),
    ]);
    Auth::login($user);
    return redirect('/dashboard');
})->name('register.post');

Route::post('/logout', function () {
    Auth::logout();
    return redirect('/login');
})->name('logout');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/sub-users', [SubUserController::class, 'index'])->name('sub-users.index');
    Route::get('/sub-users/create', [SubUserController::class, 'create'])->name('sub-users.create');
    Route::post('/sub-users', [SubUserController::class, 'store'])->name('sub-users.store');
    Route::get('/sub-users/{subUser}', [SubUserController::class, 'show'])->name('sub-users.show');
    Route::get('/sub-users/{subUser}/edit', [SubUserController::class, 'edit'])->name('sub-users.edit');
    Route::put('/sub-users/{subUser}', [SubUserController::class, 'update'])->name('sub-users.update');
    Route::delete('/sub-users/{subUser}', [SubUserController::class, 'destroy'])->name('sub-users.destroy');
    
    Route::get('/statistics', [StatisticsController::class, 'index'])->name('statistics.index');
});

Route::post('/webhook/payment', [WebhookController::class, 'handlePayment'])
    ->name('webhook.payment');

// Health check routes
Route::get('/health', [App\Http\Controllers\HealthCheckController::class, 'check'])->name('health.check');
Route::get('/ping', [App\Http\Controllers\HealthCheckController::class, 'ping'])->name('health.ping');
