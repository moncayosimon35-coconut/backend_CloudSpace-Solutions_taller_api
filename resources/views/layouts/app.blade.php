<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CloudSpace Solutions')</title>
    <style>
        :root { --brand: #4f46e5; --bg: #f4f5fb; --text: #1f2937; --muted: #6b7280; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, sans-serif; background: var(--bg); color: var(--text); }
        nav { background: #fff; border-bottom: 1px solid #e5e7eb; padding: 14px 24px; display: flex; gap: 20px; align-items: center; }
        nav strong { color: var(--brand); margin-right: auto; }
        nav a { color: var(--text); text-decoration: none; font-size: 14px; }
        nav a:hover { color: var(--brand); }
        main { max-width: 520px; margin: 40px auto; padding: 0 16px; }
        .card { background: #fff; border-radius: 12px; padding: 28px; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
        h1 { margin-top: 0; font-size: 22px; }
        label { display: block; font-size: 14px; margin: 16px 0 6px; font-weight: 600; }
        input, select, textarea { width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; font: inherit; }
        textarea { min-height: 120px; resize: vertical; }
        button { margin-top: 20px; width: 100%; padding: 12px; background: var(--brand); color: #fff; border: 0; border-radius: 8px; font: inherit; font-weight: 600; cursor: pointer; }
        button:disabled { opacity: .6; cursor: not-allowed; }
        .hint { color: var(--muted); font-size: 13px; margin-top: 4px; }
        .alert { padding: 12px 14px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
        .alert-ok { background: #ecfdf5; color: #065f46; }
        .alert-error { background: #fef2f2; color: #991b1b; }
        #card-element { padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; }
    </style>
</head>
<body>
    <nav>
        <strong>CloudSpace Solutions</strong>
        <a href="{{ route('checkout.show') }}">Checkout</a>
        <a href="{{ route('email.show') }}">Correo</a>
    </nav>
    <main>
        @yield('content')
    </main>
    @stack('scripts')
</body>
</html>
