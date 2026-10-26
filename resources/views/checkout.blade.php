@extends('layouts.app')

@section('title', 'Checkout - CloudSpace Solutions')

@section('content')
<div class="card">
    <h1>Pagar reserva</h1>

    <div id="message" class="alert" style="display:none"></div>

    {{-- RF 1.1 — formulario con monto y moneda --}}
    <form id="payment-form">
        <label for="amount">Monto</label>
        <input type="number" id="amount" name="amount" step="0.01" min="0.5" placeholder="25.00" required>

        <label for="currency">Moneda</label>
        <select id="currency" name="currency">
            @foreach ($currencies as $currency)
                <option value="{{ $currency }}">{{ strtoupper($currency) }}</option>
            @endforeach
        </select>

        <label for="email">Correo para el comprobante (opcional)</label>
        <input type="email" id="email" name="email" placeholder="tucorreo@ejemplo.com">

        {{-- RF 1.3 — Stripe Elements monta aquí el campo de tarjeta --}}
        <label>Tarjeta</label>
        <div id="card-element"></div>
        <p class="hint">Prueba con 4242 4242 4242 4242, cualquier fecha futura y cualquier CVC.</p>

        <button type="submit" id="submit-btn">Pagar</button>
    </form>
</div>
@endsection

@push('scripts')
<script src="https://js.stripe.com/v3/"></script>
<script>
    const stripe = Stripe(@json($stripeKey));
    const elements = stripe.elements();
    const card = elements.create('card');
    card.mount('#card-element');

    const form = document.getElementById('payment-form');
    const button = document.getElementById('submit-btn');
    const message = document.getElementById('message');
    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    function show(text, ok) {
        message.textContent = text;
        message.className = 'alert ' + (ok ? 'alert-ok' : 'alert-error');
        message.style.display = 'block';
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        button.disabled = true;
        message.style.display = 'none';

        try {
            // 1) Pedimos al backend que cree el PaymentIntent y nos devuelva el clientSecret.
            const response = await fetch(@json(route('checkout.intent')), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({
                    amount: form.amount.value,
                    currency: form.currency.value,
                    email: form.email.value || null,
                }),
            });
            const data = await response.json();

            if (!response.ok) {
                const detail = data.errors ? Object.values(data.errors).flat().join(' ') : '';
                show((data.message || 'Error al crear el pago.') + ' ' + detail, false);
                return;
            }

            // 2) Confirmamos el pago con la tarjeta, directamente contra Stripe (los datos de la tarjeta nunca pasan por nuestro servidor).
            const result = await stripe.confirmCardPayment(data.clientSecret, {
                payment_method: {
                    card: card,
                    billing_details: form.email.value ? { email: form.email.value } : {},
                },
            });

            if (result.error) {
                show(result.error.message, false);
            } else if (result.paymentIntent.status === 'succeeded') {
                show('¡Pago exitoso! Referencia: ' + result.paymentIntent.id, true);
                form.reset();
                card.clear();
            }
        } catch (error) {
            show('Error de conexión. Intenta de nuevo.', false);
        } finally {
            button.disabled = false;
        }
    });
</script>
@endpush
