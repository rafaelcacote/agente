<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — Agente</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f1f5f9;
            color: #1e293b;
            min-height: 100vh;
        }
        a { color: #4f46e5; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .admin-shell { display: flex; min-height: 100vh; }
        .sidebar {
            width: 240px;
            background: #1a1a2e;
            color: #e2e8f0;
            padding: 1.5rem 1rem;
            flex-shrink: 0;
        }
        .sidebar h1 { font-size: 1.1rem; margin-bottom: 1.5rem; color: #fff; }
        .sidebar nav { display: flex; flex-direction: column; gap: .35rem; }
        .sidebar a {
            color: #cbd5e1;
            padding: .55rem .75rem;
            border-radius: 8px;
            font-size: .9rem;
            text-decoration: none;
        }
        .sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,.1); color: #fff; text-decoration: none; }
        .sidebar .logout { margin-top: 2rem; }
        .main { flex: 1; padding: 1.5rem 2rem; overflow-x: auto; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem; }
        .page-header h2 { font-size: 1.5rem; }
        .card {
            background: #fff;
            border-radius: 12px;
            padding: 1.25rem;
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
            margin-bottom: 1.25rem;
        }
        .card h3 { font-size: 1rem; margin-bottom: 1rem; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .stat { background: #fff; border-radius: 12px; padding: 1.25rem; box-shadow: 0 1px 4px rgba(0,0,0,.06); }
        .stat .label { font-size: .8rem; color: #64748b; margin-bottom: .35rem; }
        .stat .value { font-size: 1.75rem; font-weight: 700; color: #1a1a2e; }
        .btn {
            display: inline-block;
            padding: .55rem 1rem;
            border-radius: 8px;
            font-size: .875rem;
            font-weight: 500;
            border: none;
            cursor: pointer;
            text-decoration: none;
            line-height: 1.2;
        }
        .btn-primary { background: #4f46e5; color: #fff; }
        .btn-primary:hover { background: #4338ca; text-decoration: none; color: #fff; }
        .btn-secondary { background: #e2e8f0; color: #334155; }
        .btn-danger { background: #dc2626; color: #fff; }
        .btn-sm { padding: .35rem .7rem; font-size: .8rem; }
        .alert {
            padding: .75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: .875rem;
        }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .alert-error { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }
        .alert-warning { background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; }
        table { width: 100%; border-collapse: collapse; font-size: .875rem; }
        th, td { text-align: left; padding: .65rem .75rem; border-bottom: 1px solid #e2e8f0; }
        th { color: #64748b; font-weight: 600; font-size: .75rem; text-transform: uppercase; }
        .badge {
            display: inline-block;
            padding: .2rem .55rem;
            border-radius: 999px;
            font-size: .7rem;
            font-weight: 600;
        }
        .badge-green { background: #dcfce7; color: #166534; }
        .badge-gray { background: #f1f5f9; color: #475569; }
        .badge-red { background: #fee2e2; color: #b91c1c; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; font-size: .85rem; font-weight: 500; margin-bottom: .35rem; color: #334155; }
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"],
        .form-group input[type="date"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: .6rem .75rem;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: .9rem;
            font-family: inherit;
        }
        .form-group textarea { min-height: 100px; resize: vertical; }
        .form-group .hint { font-size: .75rem; color: #64748b; margin-top: .25rem; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .form-check { display: flex; align-items: center; gap: .5rem; }
        .form-check input { width: auto; }
        .error { color: #dc2626; font-size: .8rem; margin-top: .25rem; }
        pre.snippet {
            background: #1e293b;
            color: #e2e8f0;
            padding: 1rem;
            border-radius: 8px;
            font-size: .78rem;
            overflow-x: auto;
            white-space: pre-wrap;
            word-break: break-all;
        }
        .actions { display: flex; gap: .5rem; flex-wrap: wrap; }
        .pagination { margin-top: 1rem; font-size: .875rem; }
        .msg { padding: .65rem .85rem; border-radius: 10px; margin-bottom: .6rem; max-width: 85%; font-size: .875rem; line-height: 1.5; }
        .msg-user { background: #e0e7ff; margin-left: auto; }
        .msg-assistant { background: #f1f5f9; }
        .msg-meta { font-size: .7rem; color: #94a3b8; margin-top: .2rem; }
        .filters { display: flex; flex-wrap: wrap; gap: .75rem; align-items: flex-end; margin-bottom: 1rem; }
        .filters .form-group { margin-bottom: 0; min-width: 140px; }
        @media (max-width: 768px) {
            .admin-shell { flex-direction: column; }
            .sidebar { width: 100%; }
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="admin-shell">
    <aside class="sidebar">
        <h1>Agente Admin</h1>
        <nav>
            <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Dashboard</a>
            <a href="{{ route('admin.tenants.index') }}" class="{{ request()->routeIs('admin.tenants.*') ? 'active' : '' }}">Tenants</a>
            <a href="{{ route('admin.conversations.index') }}" class="{{ request()->routeIs('admin.conversations.*') ? 'active' : '' }}">Conversas</a>
        </nav>
        <form action="{{ route('admin.logout') }}" method="POST" class="logout">
            @csrf
            <button type="submit" class="btn btn-secondary btn-sm" style="width:100%">Sair</button>
        </form>
    </aside>
    <main class="main">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif
        @yield('content')
    </main>
</div>
</body>
</html>
