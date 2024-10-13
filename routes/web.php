<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\CreateController;

use App\Http\Controllers\ViewController;

use App\Http\Middleware\GetUserRole;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/account-deletion', function () {
    return view('account-deletion');
});


// Route::get('/login', function(){})->name('login.view');
Route::get('/login_blade', [ViewController::class, 'login_view'])->name('login.view');

Route::post('/login', [CreateController::class, 'login'])->name('login');

// Route::get('/view_user', function () {
//     return view('view_user');
// })->name('view_user.page');
Route::get('/view_blade', [ViewController::class, 'user_view'])->name('view_user.page');


Route::prefix('admin')->middleware(['auth:sanctum', GetUserRole::class . ':admin'])->group(function () {
    Route::get('/view_user', [ViewController::class, 'user'])->name('view_user.api');
    Route::get('/logout', [CreateController::class, 'logout'])->name('logout');
});