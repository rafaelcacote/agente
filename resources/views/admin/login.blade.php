<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Agente Admin</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #1a1a2e, #2d2d5e);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .card {
            background: #fff;
            border-radius: 16px;
            padding: 2rem;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 8px 32px rgba(0,0,0,.2);
        }
        h1 { font-size: 1.35rem; margin-bottom: .25rem; color: #1a1a2e; }
        p { color: #64748b; font-size: .9rem; margin-bottom: 1.5rem; }
        label { display: block; font-size: .85rem; font-weight: 500; margin-bottom: .35rem; }
        input {
            width: 100%;
            padding: .65rem .75rem;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: .9rem;
            margin-bottom: 1rem;
        }
        button {
            width: 100%;
            padding: .7rem;
            background: #4f46e5;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: .95rem;
            cursor: pointer;
        }
        button:hover { background: #4338ca; }
        .error { color: #dc2626; font-size: .85rem; margin-bottom: 1rem; }
    </style>
</head>
<body>
<div class="card">
    <h1>Agente Admin</h1>
    <p>Acesso ao painel de gestão</p>

    @if($errors->any())
        <div class="error">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('admin.login.submit') }}">
        @csrf
        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>

        <label for="password">Senha</label>
        <input type="password" id="password" name="password" required>

        <button type="submit">Entrar</button>
    </form>
</div>
</body>
</html>
