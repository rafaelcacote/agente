@extends('admin.layout')

@section('title', 'Dashboard')

@section('content')
<div class="page-header">
    <h2>Dashboard</h2>
</div>

<div class="stats">
    <div class="stat">
        <div class="label">Tenants ativos</div>
        <div class="value">{{ $stats['tenants_active'] }}</div>
    </div>
    <div class="stat">
        <div class="label">Conversas hoje</div>
        <div class="value">{{ $stats['conversations_today'] }}</div>
    </div>
    <div class="stat">
        <div class="label">Mensagens hoje</div>
        <div class="value">{{ $stats['messages_today'] }}</div>
    </div>
    <div class="stat">
        <div class="label">Tokens hoje</div>
        <div class="value">{{ number_format($stats['tokens_today']) }}</div>
    </div>
    <div class="stat">
        <div class="label">Conversas ativas</div>
        <div class="value">{{ $stats['conversations_active'] }}</div>
    </div>
</div>

<div class="card">
    <h3>Conversas recentes</h3>
    @if($recentConversations->isEmpty())
        <p style="color:#64748b;font-size:.9rem">Nenhuma conversa registrada ainda.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Tenant</th>
                    <th>Origem</th>
                    <th>Status</th>
                    <th>Mensagens</th>
                    <th>Data</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentConversations as $conversation)
                    <tr>
                        <td>{{ $conversation->tenant?->name ?? '—' }}</td>
                        <td>{{ $conversation->source }}</td>
                        <td>
                            <span class="badge {{ $conversation->status === 'active' ? 'badge-green' : 'badge-gray' }}">
                                {{ $conversation->status }}
                            </span>
                        </td>
                        <td>{{ $conversation->messages_count }}</td>
                        <td>{{ $conversation->created_at->format('d/m/Y H:i') }}</td>
                        <td><a href="{{ route('admin.conversations.show', $conversation) }}">Ver</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
