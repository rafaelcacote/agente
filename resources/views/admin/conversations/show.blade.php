@extends('admin.layout')

@section('title', 'Conversa')

@section('content')
<div class="page-header">
    <h2>Conversa</h2>
    <div class="actions">
        @if($conversation->isActive())
            <form method="POST" action="{{ route('admin.conversations.close', $conversation) }}" onsubmit="return confirm('Encerrar esta conversa?')">
                @csrf
                <button type="submit" class="btn btn-danger btn-sm">Encerrar conversa</button>
            </form>
        @endif
        <a href="{{ route('admin.conversations.index') }}" class="btn btn-secondary btn-sm">Voltar</a>
    </div>
</div>

<div class="stats" style="margin-bottom:1.25rem">
    <div class="stat">
        <div class="label">Status</div>
        <div class="value" style="font-size:1.1rem">{{ $conversation->status }}</div>
    </div>
    <div class="stat">
        <div class="label">Mensagens</div>
        <div class="value" style="font-size:1.1rem">{{ $conversation->messages->count() }}</div>
    </div>
    <div class="stat">
        <div class="label">Tokens total</div>
        <div class="value" style="font-size:1.1rem">{{ number_format($tokenStats['total']) }}</div>
    </div>
</div>

<div class="card">
    <h3>Detalhes</h3>
    <table>
        <tr><th style="width:140px">UUID</th><td><code>{{ $conversation->uuid }}</code></td></tr>
        <tr><th>Tenant</th><td>{{ $conversation->tenant?->name }} (<code>{{ $conversation->tenant?->slug }}</code>)</td></tr>
        <tr><th>Origem</th><td>{{ $conversation->source }}</td></tr>
        <tr><th>IP</th><td>{{ $conversation->ip_address ?? '—' }}</td></tr>
        <tr><th>User-Agent</th><td style="word-break:break-all">{{ $conversation->user_agent ?? '—' }}</td></tr>
        <tr><th>Modelo</th><td>{{ $conversation->model ?? '—' }}</td></tr>
        <tr><th>Criada em</th><td>{{ $conversation->created_at->format('d/m/Y H:i:s') }}</td></tr>
        <tr><th>Tokens</th><td>prompt: {{ $tokenStats['prompt'] }} · completion: {{ $tokenStats['completion'] }} · total: {{ $tokenStats['total'] }}</td></tr>
        @if($conversation->metadata)
            <tr><th>Metadata</th><td><pre style="font-size:.8rem;white-space:pre-wrap">{{ json_encode($conversation->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre></td></tr>
        @endif
    </table>
</div>

<div class="card">
    <h3>Mensagens</h3>
    @forelse($conversation->messages as $message)
        <div class="msg {{ $message->isFromUser() ? 'msg-user' : 'msg-assistant' }}" style="{{ $message->isFromUser() ? 'margin-left:auto' : '' }}">
            {{ $message->content }}
            <div class="msg-meta">
                {{ $message->role }}
                · {{ $message->created_at->format('d/m/Y H:i') }}
                @if($message->total_tokens)
                    · {{ $message->total_tokens }} tokens
                    @if($message->model) · {{ $message->model }} @endif
                @endif
            </div>
        </div>
    @empty
        <p style="color:#64748b;font-size:.9rem">Sem mensagens.</p>
    @endforelse
</div>
@endsection
