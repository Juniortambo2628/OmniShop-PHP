<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ProductApiController;
use App\Http\Controllers\Api\OrderApiController;
use App\Http\Controllers\Api\StockApiController;
use App\Http\Controllers\Api\SettingsApiController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\AnalyticsApiController;
use App\Http\Controllers\Api\DraftApiController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\NotificationController;

use App\Http\Controllers\Api\PublicCatalogController;
use App\Http\Controllers\Api\Admin\StorefrontCmsController;
use App\Http\Controllers\PromoCodeController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\Api\InvoiceApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public auth routes
use App\Http\Controllers\Api\ProductImageController;

Route::post('/login', [AuthController::class, 'login']);

// Public catalog routes
Route::post('/catalog/{event}/login', [PublicCatalogController::class, 'login']);
Route::get('/catalog/{event}/data', [PublicCatalogController::class, 'data']);
Route::post('/catalog/{event}/checkout', [PublicCatalogController::class, 'checkout']);
Route::get('/storefront/settings', [StorefrontCmsController::class, 'index']); // Public access to CMS settings
Route::post('/promo-codes/validate', [PromoCodeController::class, 'validateCode']);
Route::post('/feedback', [FeedbackController::class, 'store']);

// Protected admin routes
Route::middleware('auth:sanctum')->group(function () {
    \Illuminate\Support\Facades\Broadcast::routes(['middleware' => ['auth:sanctum']]);

    // Auth
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Dashboard & Analytics
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/analytics', [AnalyticsApiController::class, 'index']);

    // Orders
    Route::get('/orders', [OrderApiController::class, 'index']);
    Route::get('/orders/{id}', [OrderApiController::class, 'show']);
    Route::put('/orders/{id}/status', [OrderApiController::class, 'updateStatus']);
    Route::post('/orders/{id}/send-email', [OrderApiController::class, 'sendEmail']);
    Route::post('/orders/bulk-delete', [OrderApiController::class, 'bulkDelete']);
    Route::post('/orders/bulk-status', [OrderApiController::class, 'bulkUpdateStatus']);
    Route::get('/orders/{id}/invoice/pdf', [InvoiceApiController::class, 'streamPdf']);
    Route::get('/orders/{id}/invoice/download', [InvoiceApiController::class, 'downloadPdf']);

    // Payments
    Route::apiResource('/payments', PaymentController::class)->except(['update']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::put('/notifications/mark-all-read', [NotificationController::class, 'markAllRead']);
    Route::put('/notifications/{id}/mark-read', [NotificationController::class, 'markAsRead']);

    // Products
    Route::get('/products', [ProductApiController::class, 'index']);
    Route::get('/products/{id}', [ProductApiController::class, 'show']);
    Route::get('/products/catalog/{catalogId}', [ProductApiController::class, 'showCatalog']);
    Route::post('/products', [ProductApiController::class, 'store']);
    Route::put('/products/{id}', [ProductApiController::class, 'update']);
    Route::post('/products/bulk-delete', [ProductApiController::class, 'bulkDelete']);
    Route::post('/products/bulk-status', [ProductApiController::class, 'bulkUpdateStatus']);
    Route::get('/products/code/{code}/images', [ProductImageController::class, 'listByCode']);
    Route::post('/products/images', [ProductImageController::class, 'upload']);
    Route::delete('/products/images', [ProductImageController::class, 'delete']);

    // Stock
    Route::get('/stock', [StockApiController::class, 'index']);
    Route::put('/stock/{productId}', [StockApiController::class, 'update']);
    Route::post('/stock/bulk-reset', [StockApiController::class, 'bulkReset']);

    // Settings
    Route::get('/settings', [SettingsApiController::class, 'index']);
    Route::put('/settings', [SettingsApiController::class, 'update']);

    // Global search
    Route::get('/search', [SearchController::class, 'search']);

    // Categories (read-only from config)
    Route::get('/categories', function () {
        return response()->json(collect(config('catalog.categories'))->pluck('name', 'id')->toArray());
    });

    Route::get('/events', function () {
        return response()->json(collect(config('events', []))->map(fn($e, $slug) => [
            'slug' => $slug,
            'short_name' => $e['short_name'] ?? $slug,
        ])->values());
    });

    // Drafts
    Route::get('/drafts', [DraftApiController::class, 'getDraft']);
    Route::post('/drafts', [DraftApiController::class, 'saveDraft']);
    Route::delete('/drafts', [DraftApiController::class, 'deleteDraft']);

    // Storefront CMS
    Route::get('/admin/storefront/settings', [StorefrontCmsController::class, 'index']);
    Route::post('/admin/storefront/settings', [StorefrontCmsController::class, 'store']);

    // Promo Codes Management
    Route::apiResource('admin/promo-codes', PromoCodeController::class);

    // Feedback Management
    Route::get('/admin/feedback', [FeedbackController::class, 'index']);
    Route::delete('/admin/feedback/{id}', [FeedbackController::class, 'destroy']);

    // Email Testing
    Route::post('/admin/send-test-email', [OrderApiController::class, 'sendTestEmail']);
});
