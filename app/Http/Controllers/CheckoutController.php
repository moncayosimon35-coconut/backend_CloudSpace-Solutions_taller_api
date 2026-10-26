<?php

namespace App\Http\Controllers;

use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Stripe\Exception\ApiErrorException;

/**
 * Solo gestiona el flujo HTTP (RNF 1): la lógica de Stripe está en StripeService.
 */
class CheckoutController extends Controller
{
    public function __construct(private StripeService $stripe)
    {
    }

    /** RF 1.1 — GET /checkout */
    public function show(): View
    {
        return view('checkout', [
            'stripeKey'  => config('services.stripe.key'),
            'currencies' => StripeService::CURRENCIES,
        ]);
    }

    /** RF 1.2 — POST /create-payment-intent (responde JSON, RNF 3) */
    public function createPaymentIntent(Request $request): JsonResponse
    {
        try {
            $result = $this->stripe->createPaymentIntent($request->only(['amount', 'currency', 'email']));

            return response()->json(['clientSecret' => $result['clientSecret']], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Datos inválidos.',
                'errors'  => $e->errors(),
            ], 400);
        } catch (ApiErrorException $e) {
            Log::error('Stripe: ' . $e->getMessage());

            return response()->json(['message' => 'Stripe no pudo crear el pago.'], 500);
        } catch (\Throwable $e) {
            Log::error('Error creando PaymentIntent: ' . $e->getMessage());

            return response()->json(['message' => 'Error interno del servidor.'], 500);
        }
    }
}
