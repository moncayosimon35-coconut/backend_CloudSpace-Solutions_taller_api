<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Stripe\Event;
use Stripe\StripeClient;
use Stripe\Webhook;

/**
 * Capa de servicio para Stripe (RNF 1).
 * Toda la comunicación con la API de Stripe vive aquí, no en controladores ni modelos.
 */
class StripeService
{
    /** Monedas que ofrece el formulario de checkout. */
    public const CURRENCIES = ['usd', 'eur', 'gbp', 'cop', 'mxn'];

    /** Monedas sin decimales: Stripe espera el monto tal cual (sin multiplicar por 100). */
    private const ZERO_DECIMAL = [
        'bif', 'clp', 'djf', 'gnf', 'jpy', 'kmf', 'krw', 'mga',
        'pyg', 'rwf', 'ugx', 'vnd', 'vuv', 'xaf', 'xof', 'xpf',
    ];

    private ?StripeClient $client = null;

    public function __construct(private SendGridService $mailer)
    {
    }

    /** Cliente de Stripe creado de forma perezosa con STRIPE_SECRET. */
    private function client(): StripeClient
    {
        return $this->client ??= new StripeClient((string) config('services.stripe.secret'));
    }

    /**
     * RF 1.2 — Valida los datos y crea un PaymentIntent en Stripe.
     *
     * @param  array{amount: mixed, currency: mixed, email?: mixed}  $data
     * @return array{clientSecret: string, paymentIntentId: string}
     *
     * @throws \Illuminate\Validation\ValidationException  si los datos son inválidos
     * @throws \Stripe\Exception\ApiErrorException         si Stripe rechaza la petición
     */
    public function createPaymentIntent(array $data): array
    {
        $validated = Validator::make($data, [
            'amount'   => ['required', 'numeric', 'min:0.5', 'max:999999'],
            'currency' => ['required', 'string', Rule::in(self::CURRENCIES)],
            'email'    => ['nullable', 'email'],
        ])->validate();

        $currency = strtolower($validated['currency']);

        $intent = $this->client()->paymentIntents->create([
            'amount'               => $this->toMinorUnits((float) $validated['amount'], $currency),
            'currency'             => $currency,
            'payment_method_types' => ['card'],
            'metadata'             => array_filter(['email' => $validated['email'] ?? null]),
        ]);

        return [
            'clientSecret'    => $intent->client_secret,
            'paymentIntentId' => $intent->id,
        ];
    }

    /**
     * RF 2.1 — Verifica la firma del webhook y procesa el evento.
     *
     * @throws \UnexpectedValueException                          payload inválido
     * @throws \Stripe\Exception\SignatureVerificationException   firma inválida
     */
    public function handleWebhook(string $payload, ?string $signature): void
    {
        $event = Webhook::constructEvent(
            $payload,
            (string) $signature,
            (string) config('services.stripe.webhook_secret')
        );

        $this->processEvent($event);
    }

    private function processEvent(Event $event): void
    {
        switch ($event->type) {
            case 'payment_intent.succeeded':
                $this->recordPayment($event->data->object, 'succeeded');
                break;

            case 'payment_intent.payment_failed':
                $this->recordPayment($event->data->object, 'failed');
                break;

            default:
                // Otros eventos se ignoran, pero igual respondemos 200 para que Stripe no reintente.
                break;
        }
    }

    /** Registra (o actualiza) el pago en MySQL. Es idempotente: Stripe puede reenviar eventos. */
    private function recordPayment($intent, string $status): void
    {
        $payment = Payment::firstOrNew(['stripe_payment_intent_id' => $intent->id]);
        $alreadyPaid = $payment->status === 'succeeded';

        $payment->fill([
            'amount'   => $intent->amount,
            'currency' => $intent->currency,
            'status'   => $status,
            'email'    => $intent->metadata['email'] ?? $payment->email,
            'paid_at'  => $status === 'succeeded' ? now() : $payment->paid_at,
        ])->save();

        // Correo de confirmación solo la primera vez que el pago queda exitoso.
        if ($status === 'succeeded' && ! $alreadyPaid && $payment->email) {
            $this->sendConfirmation($payment);
        }
    }

    private function sendConfirmation(Payment $payment): void
    {
        try {
            $amount = number_format($payment->amount / $this->divisor($payment->currency), 2);

            $this->mailer->send(
                $payment->email,
                'Confirmación de pago - CloudSpace Solutions',
                "¡Gracias por tu pago!\n\nMonto: {$amount} " . strtoupper($payment->currency)
                . "\nReferencia: {$payment->stripe_payment_intent_id}"
            );
        } catch (\Throwable $e) {
            // Un fallo del correo no debe hacer fallar el webhook (el pago ya quedó registrado).
            Log::error('No se pudo enviar el correo de confirmación: ' . $e->getMessage());
        }
    }

    private function toMinorUnits(float $amount, string $currency): int
    {
        return (int) round($amount * $this->divisor($currency));
    }

    private function divisor(string $currency): int
    {
        return in_array(strtolower($currency), self::ZERO_DECIMAL, true) ? 1 : 100;
    }
}
