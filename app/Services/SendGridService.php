<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Capa de servicio para SendGrid (RF 3.1).
 * Consume directamente la API REST v3 de SendGrid usando el cliente HTTP de Laravel.
 */
class SendGridService
{
    private const ENDPOINT = 'https://api.sendgrid.com/v3/mail/send';

    /**
     * Envía un correo a uno o varios destinatarios.
     *
     * @param  string|array<int, string>  $to
     *
     * @throws \RuntimeException si SendGrid no acepta el mensaje
     */
    public function send(string|array $to, string $subject, string $body): bool
    {
        $recipients = array_map(
            fn (string $email) => ['email' => $email],
            (array) $to
        );

        $response = Http::withToken((string) config('services.sendgrid.api_key'))
            ->acceptJson()
            ->asJson()
            ->timeout(15)
            ->post(self::ENDPOINT, [
                'personalizations' => [['to' => $recipients]],
                'from' => [
                    'email' => config('services.sendgrid.from_email'),
                    'name'  => config('services.sendgrid.from_name'),
                ],
                'subject' => $subject,
                // SendGrid exige text/plain primero y luego text/html.
                'content' => [
                    ['type' => 'text/plain', 'value' => $body],
                    ['type' => 'text/html', 'value' => nl2br(e($body))],
                ],
            ]);

        // SendGrid responde 202 Accepted cuando encola el mensaje.
        if ($response->status() !== 202) {
            throw new RuntimeException(
                'SendGrid respondió ' . $response->status() . ': ' . $response->body()
            );
        }

        return true;
    }
}
