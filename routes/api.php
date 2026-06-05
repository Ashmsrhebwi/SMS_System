<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CampaignController;
use App\Http\Controllers\Api\V1\ContactController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\SegmentController;
use App\Http\Controllers\Api\V1\TagController;
use App\Http\Controllers\Api\V1\TemplateCategoryController;
use App\Http\Controllers\Api\V1\TemplateController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // ── Auth (public) ─────────────────────────────────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login'])
            ->middleware('throttle:5,1');
        Route::post('otp/verify', [AuthController::class, 'otpVerify'])
            ->middleware('throttle:10,1');
        Route::post('otp/resend', [AuthController::class, 'otpResend'])
            ->middleware('throttle:5,1');
        Route::post('forgot-password', [AuthController::class, 'forgotPassword'])
            ->middleware('throttle:5,1');
        Route::post('reset-password', [AuthController::class, 'resetPassword']);
    });

    // ── Authenticated ─────────────────────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);

        // Dashboard
        Route::get('dashboard', [DashboardController::class, 'index']);

        // Campaigns
        Route::apiResource('campaigns', CampaignController::class);
        Route::post('campaigns/{campaign}/send', [CampaignController::class, 'sendNow']);
        Route::post('campaigns/{campaign}/resend-failed', [CampaignController::class, 'resendFailed']);
        Route::post('campaigns/{campaign}/duplicate', [CampaignController::class, 'duplicate']);
        Route::get('campaigns/{campaign}/export', [CampaignController::class, 'exportReport']);
        Route::get('campaigns/{campaign}/messages', [CampaignController::class, 'messages']);

        // Contacts
        Route::apiResource('contacts', ContactController::class);
        Route::post('contacts/{contact}/toggle-opt-in', [ContactController::class, 'toggleOptIn']);
        Route::get('contacts/{contact}/notes', [ContactController::class, 'notes']);
        Route::post('contacts/{contact}/notes', [ContactController::class, 'storeNote']);
        Route::post('contacts/import', [ContactController::class, 'import']);
        Route::get('contacts/export', [ContactController::class, 'export']);
        Route::get('contacts/check-duplicate', [ContactController::class, 'checkDuplicate']);

        // Tags
        Route::apiResource('tags', TagController::class)->only(['index', 'store', 'update', 'destroy']);

        // Segments
        Route::apiResource('segments', SegmentController::class);
        Route::get('segments/{segment}/contacts', [SegmentController::class, 'contacts']);
        Route::get('segments/{segment}/count', [SegmentController::class, 'count']);

        // Templates
        Route::apiResource('templates', TemplateController::class);
        Route::apiResource('template-categories', TemplateCategoryController::class)->only(['index', 'store', 'update', 'destroy']);

        // Reports
        Route::get('reports/costs', [ReportController::class, 'costs']);
        Route::get('reports/countries', [ReportController::class, 'countries']);
        Route::get('reports/delivery', [ReportController::class, 'delivery']);

        // Users (admin only)
        Route::apiResource('users', UserController::class);
        Route::post('users/{user}/toggle-active', [UserController::class, 'toggleActive']);
    });
});
