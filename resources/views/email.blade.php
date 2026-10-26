@extends('layouts.app')

@section('title', 'Enviar correo - CloudSpace Solutions')

@section('content')
<div class="card">
    <h1>Enviar correo de prueba</h1>

    @if (session('status'))
        <div class="alert alert-ok">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-error">
            <ul style="margin:0; padding-left:18px">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('email.send') }}">
        @csrf

        <label for="recipients">Destinatarios</label>
        <input type="text" id="recipients" name="recipients" value="{{ old('recipients') }}" placeholder="uno@ejemplo.com, dos@ejemplo.com" required>
        <p class="hint">Separa varios correos con coma (máximo 10).</p>

        <label for="subject">Asunto</label>
        <input type="text" id="subject" name="subject" value="{{ old('subject') }}" required>

        <label for="message">Mensaje</label>
        <textarea id="message" name="message" required>{{ old('message') }}</textarea>

        <button type="submit">Enviar</button>
    </form>
</div>
@endsection