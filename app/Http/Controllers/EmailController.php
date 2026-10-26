<?php

namespace App\Http\Controllers;

use App\Services\SendGridService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class EmailController extends Controller
{
    public function __construct(private SendGridService $sendGrid)
    {
    }

    /** RF 3.2 — GET /email */
    public function show(): View
    {
        return view('email');
    }

    /** RF 3.2 — POST /email */
    public function send(Request $request): RedirectResponse
    {
        // El usuario escribe los destinatarios separados por coma.
        $request->merge([
            'recipients' => array_values(array_filter(
                array_map('trim', explode(',', (string) $request->input('recipients')))
            )),
        ]);

        $data = $request->validate([
            'recipients'   => ['required', 'array', 'min:1', 'max:10'],
            'recipients.*' => ['email'],
            'subject'      => ['required', 'string', 'max:150'],
            'message'      => ['required', 'string', 'max:5000'],
        ]);

        try {
            $this->sendGrid->send($data['recipients'], $data['subject'], $data['message']);
        } catch (\Throwable $e) {
            Log::error('Error enviando correo con SendGrid: ' . $e->getMessage());

            return back()
                ->withInput($request->except('recipients') + ['recipients' => implode(', ', $data['recipients'])])
                ->withErrors(['sendgrid' => 'No se pudo enviar el correo. Revisa la API key y el remitente verificado.']);
        }

        return redirect()->route('email.show')
            ->with('status', 'Correo enviado a: ' . implode(', ', $data['recipients']));
    }
}
