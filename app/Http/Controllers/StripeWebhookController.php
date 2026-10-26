<?php

namespace App\Http\Controllers;

use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookController extends Controller
{
    /** RF 2.1 — POST /stripe/webhook (excluida de CSRF en bootstrap/app.php) */
    public function handle(Request $request, StripeService $stripe): JsonResponse
    {
        try {
            $stripe->handleWebhook(
                $request->getContent(),            // payload crudo: la firma se calcula sobre él
                $request->header('Stripe-Signature')
            );
        } catch (\UnexpectedValueException | SignatureVerificationException $e) {
            return response()->json(['error' => 'Payload o firma inválidos.'], 400);
        } catch (\Throwable $e) {
            Log::error('Error procesando webhook de Stripe: ' . $e->getMessage());

            return response()->json(['error' => 'Error interno del servidor.'], 500);
        }

        return response()->json(['received' => true], 200);
    }
}
