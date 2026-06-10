@extends('admin.layout')

@section('title', 'Editar tenant')

@section('content')
<div class="page-header">
    <h2>{{ $tenant->name }}</h2>
    <a href="{{ route('admin.tenants.index') }}" class="btn btn-secondary">Voltar</a>
</div>

@if($newApiKey)
    <div class="alert alert-warning">
        <strong>Nova API Key (copie agora):</strong><br>
        <code style="word-break:break-all">{{ $newApiKey }}</code>
    </div>
@endif

<div class="card">
    <h3>Configuração</h3>
    <form method="POST" action="{{ route('admin.tenants.update', $tenant) }}">
        @csrf
        @method('PUT')
        @include('admin.tenants._form', ['allowedOrigins' => $allowedOrigins])
        <button type="submit" class="btn btn-primary">Salvar</button>
    </form>
</div>

<div class="card">
    <h3>API Key</h3>
    @if($tenant->hasApiKey())
        <p style="font-size:.9rem;margin-bottom:1rem">
            Prefixo atual: <code>{{ $tenant->api_key_prefix }}…</code>
        </p>
        <div class="actions">
            <form method="POST" action="{{ route('admin.tenants.api-key', $tenant) }}" onsubmit="return confirm('Gerar nova chave? A anterior deixará de funcionar.')">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm">Rotacionar chave</button>
            </form>
            <form method="POST" action="{{ route('admin.tenants.api-key.revoke', $tenant) }}" onsubmit="return confirm('Revogar a API Key? O widget parará de funcionar.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm">Revogar</button>
            </form>
        </div>
    @else
        <p style="font-size:.9rem;color:#64748b;margin-bottom:1rem">Nenhuma API Key configurada.</p>
        <form method="POST" action="{{ route('admin.tenants.api-key', $tenant) }}">
            @csrf
            <button type="submit" class="btn btn-primary btn-sm">Gerar API Key</button>
        </form>
    @endif
</div>

<div class="card">
    <h3>Snippet de integração</h3>
    <p style="font-size:.85rem;color:#64748b;margin-bottom:.75rem">
        Cole no HTML do site do cliente. Substitua <code>SUA_API_KEY_AQUI</code> pela chave gerada acima.
    </p>
    <pre class="snippet" id="embed-snippet">{{ $newApiKey ? $tenant->buildEmbedSnippet($newApiKey) : $embedSnippet }}</pre>
    <button type="button" class="btn btn-secondary btn-sm" style="margin-top:.75rem" onclick="navigator.clipboard.writeText(document.getElementById('embed-snippet').textContent)">Copiar snippet</button>
</div>

@if(!$tenant->conversations()->exists())
    <div class="card">
        <form method="POST" action="{{ route('admin.tenants.destroy', $tenant) }}" onsubmit="return confirm('Excluir este tenant permanentemente?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger btn-sm">Excluir tenant</button>
        </form>
    </div>
@endif
@endsection
