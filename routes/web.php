<?php

use App\Http\Controllers\Payment\PaymentController as PaymentPaymentController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

/*
    Route::post('/payments/pay', [App\Http\Controllers\Payment\PaymentController::class, 'pay'])->name('pay');
    Route::get('/payments/approval', [App\Http\Controllers\Payment\PaymentController::class, 'approval'])->name('approval');
    Route::get('/payments/cancelled', [App\Http\Controllers\Payment\PaymentController::class, 'cancelled'])->name('cancelled');
*/



Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

/*
Route::prefix('subscribe')
    ->name('subscribe.')
    ->group(function() {
        Route::get('/', [App\Http\Controllers\Payment\SubscriptionController::class, 'show'])->name('show'); //subscribe.show
        Route::post('/', [App\Http\Controllers\Payment\SubscriptionController::class, 'store'])->name('store'); //subscribe.store
        Route::get('/approval', [App\Http\Controllers\Payment\SubscriptionController::class, 'approval'])->name('approval'); //subscribe.approval
        Route::get('/cancelled', [App\Http\Controllers\Payment\SubscriptionController::class, 'cancelled'])->name('cancelled'); //subscribe.approval
    });
*/