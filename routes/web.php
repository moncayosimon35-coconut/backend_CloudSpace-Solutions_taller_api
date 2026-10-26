<?php

use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('checkout.show'));

// Módulo 1 — Pagos (Stripe)
Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
Route::post('/create-payment-intent', [CheckoutController::class, 'createPaymentIntent'])->name('checkout.intent');

// Módulo 2 — Webhook (excluido de CSRF en bootstrap/app.php)
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle'])->name('stripe.webhook');

// Módulo 3 — Correos (SendGrid)
Route::get('/email', [EmailController::class, 'show'])->name('email.show');
Route::post('/email', [EmailController::class, 'send'])->name('email.send');