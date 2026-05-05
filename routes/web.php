<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\StockController;
use App\Http\Controllers\Admin\SettingController;

Route::get('/', function () {
    return redirect('/solarandstorage');
});

// API (For Order Submission)
Route::post('/api/submit-order', [\App\Http\Controllers\Api\OrderController::class, 'submit'])->name('api.order.submit');

// Admin
Route::prefix('admin')->middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');
    
    Route::get('/products', [ProductController::class, 'index'])->name('admin.products');
    Route::get('/products/add', [ProductController::class, 'create'])->name('admin.products.create');
    Route::post('/products/add', [ProductController::class, 'store']);
    Route::get('/products/{id}/edit', [ProductController::class, 'edit'])->name('admin.products.edit');
    Route::post('/products/{id}/edit', [ProductController::class, 'update']);
    
    Route::get('/stock', [StockController::class, 'index'])->name('admin.stock');
    Route::post('/stock', [StockController::class, 'update']);
    
    Route::get('/orders', [OrderController::class, 'index'])->name('admin.orders');
    Route::get('/order/{id}', [OrderController::class, 'show'])->name('admin.orders.show');
    Route::post('/order/{id}', [OrderController::class, 'update']);
    
    Route::get('/settings', [SettingController::class, 'index'])->name('admin.settings');
    Route::post('/settings', [SettingController::class, 'update']);
});

// Auth Routes (Login)
Route::get('admin/login', [DashboardController::class, 'loginForm'])->name('login')->withoutMiddleware('auth');
Route::post('admin/login', [DashboardController::class, 'login'])->withoutMiddleware('auth');
Route::post('admin/logout', [DashboardController::class, 'logout'])->name('logout');

// Storefront
Route::prefix('{event_slug}')->group(function () {
    Route::get('/login', [CatalogController::class, 'loginForm'])->name('catalog.login');
    Route::post('/login', [CatalogController::class, 'login'])->name('catalog.login.post');
    
    Route::middleware('catalog.auth')->group(function () {
        Route::get('/', [CatalogController::class, 'index'])->name('catalog');
        Route::get('/checkout', [CatalogController::class, 'checkout'])->name('catalog.checkout');
        Route::get('/confirmation/{order_id}', [CatalogController::class, 'confirmation'])->name('catalog.confirmation');
    });
});


