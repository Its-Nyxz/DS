<?php

use Livewire\Volt\Volt;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CompanieController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReturController;
use App\Http\Controllers\StockInController;
use App\Http\Controllers\StockOutController;
use App\Http\Controllers\StockReturController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\StockTransactionController;
use App\Http\Controllers\UserController;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

// ✅ Master Data
Route::resource('units', UnitController::class);
Route::resource('brands', BrandController::class);
Route::resource('items', ItemController::class);

// ✅ Supplier
Route::resource('suppliers', SupplierController::class);

// ✅ Transaksi
Route::resource('transactions', StockTransactionController::class);
Route::resource('stockin', StockInController::class);
Route::resource('stockout', StockOutController::class);
Route::resource('stockretur', ReturController::class);

// ✅ Laporan
Route::get('reports/{type}', [ReportController::class, 'index'])->name('reports.index');
// Route::resource('reports', ReportController::class);
// user
Route::resource('users', UserController::class);
// company
Route::resource('companie', CompanieController::class);


require __DIR__ . '/auth.php';
