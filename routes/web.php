<?php

use Livewire\Volt\Volt;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CashTransactionController;
use App\Http\Controllers\CompanieController;
use App\Http\Controllers\ItemSupplierController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReturController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\StockInController;
use App\Http\Controllers\StockOpnameController;
use App\Http\Controllers\StockOutController;
use App\Http\Controllers\StockReturController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\StockTransactionController;
use App\Http\Controllers\TransactionController;
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
Route::get('/units/template', [UnitController::class, 'export'])->name('units.template');
Route::resource('units', UnitController::class);
Route::get('/brands/template', [BrandController::class, 'export'])->name('brands.template');
Route::resource('brands', BrandController::class);
Route::get('/items/template', [ItemController::class, 'export'])->name('items.template');
Route::resource('items', ItemController::class);

// ✅ Supplier
Route::get('/suppliers/template', [SupplierController::class, 'export'])->name('suppliers.template');
Route::resource('suppliers', SupplierController::class);

// item supplier
Route::resource('itemsuppliers', ItemSupplierController::class);

// ✅ Transaksi
Route::get('transactions/{type}', [TransactionController::class, 'index'])
    ->name('transactions.index')
    ->where('type', 'in|out|retur|opname');

// ✅ Kas
Route::get('cashtransactions/{type}', [CashTransactionController::class, 'index'])
    ->name('cashtransactions.index')
    ->where('type', 'utang|piutang|arus');
// Route::resource('transactions', TransactionController::class);
// Route::resource('stockin', StockInController::class);
// Route::resource('stockout', StockOutController::class);
// Route::resource('stockretur', ReturController::class);
// Route::resource('stockopname', StockOpnameController::class);

// ✅ Laporan transaksi
Route::get('reports/{type}', [ReportController::class, 'index'])
    ->name('reports.index')
    ->where('type', 'in|out|retur');
// Route::resource('reports', ReportController::class);

// laporan stok
Route::resource('reportstock', StockTransactionController::class);
// user
Route::resource('users', UserController::class);
// company
Route::resource('companie', CompanieController::class);
// role
Route::resource('permissions', RoleController::class);


require __DIR__ . '/auth.php';
