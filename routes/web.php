<?php

use App\Http\Controllers\CampaignController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OptOutController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('dashboard'));

// Public routes
Route::get('/r/{token}', [TrackingController::class, 'click'])->name('track.click');
Route::get('/optout', [OptOutController::class, 'form'])->name('optout.form');
Route::post('/optout', [OptOutController::class, 'process'])->name('optout.process');

// Twilio webhook — public but signature-validated inside controller
Route::post('/webhooks/twilio/status', [WebhookController::class, 'twilioStatus'])
    ->name('webhooks.twilio.status');

// Authenticated routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Contacts
    Route::get('/contacts', [ContactController::class, 'index'])->name('contacts.index');
    Route::get('/contacts/import', [ContactController::class, 'importForm'])->name('contacts.import');
    Route::post('/contacts/import', [ContactController::class, 'import'])->name('contacts.import.store');
    Route::patch('/contacts/{contact}/toggle-opt-in', [ContactController::class, 'toggleOptIn'])->name('contacts.toggle-opt-in');
    Route::delete('/contacts/{contact}', [ContactController::class, 'destroy'])->name('contacts.destroy');

    // Campaigns
    Route::get('/campaigns', [CampaignController::class, 'index'])->name('campaigns.index');
    Route::get('/campaigns/create', [CampaignController::class, 'create'])->name('campaigns.create');
    Route::post('/campaigns', [CampaignController::class, 'store'])->name('campaigns.store');
    Route::get('/campaigns/{campaign}', [CampaignController::class, 'show'])->name('campaigns.show');
    Route::post('/campaigns/{campaign}/send-now', [CampaignController::class, 'sendNow'])->name('campaigns.send-now');
    Route::get('/campaigns/{campaign}/export', [CampaignController::class, 'exportReport'])->name('campaigns.export');
    Route::delete('/campaigns/{campaign}', [CampaignController::class, 'destroy'])->name('campaigns.destroy');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
