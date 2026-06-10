@extends('admin.layout')

@section('title', 'Conversas')

@section('content')
<div class="page-header">
    <h2>Conversas</h2>
</div>

<div class="card">
    <form method="GET" class="filters">
        <div class="form-group">
            <label for="tenant_id">Tenant</label>
            <select id="tenant_id" name="tenant_id">
                <option value="">Todos</option>
                @foreach($tenants as $tenant)
                    <option value="{{ $tenant->id }}" @selected(request('tenant_id') == $tenant->id)>{{ $tenant->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">Todos</option>
                <option value="active" @selected(request('status') === 'active')>active</option>
                <option value="closed" @selected(request('status') === 'closed')>closed</option>
                <option value="transferred" @selected(request('status') === 'transferred')>transferred</option>
            </select>
        </div>
        <div class="form-group">
            <label for="source">Origem</label>
            <input type="text" id="source" name="source" value="{{ request('source') }}" placeholder="widget, web...">
        </div>
        <div class="form-group">
            <label for="date_from">De</label>
            <input type="date" id="date_from" name="date_from" value="{{ request('date_from') }}">
        </div>
        <div class="form-group">
            <label for="date_to">Até</label>
            <input type="date" id="date_to" name="date_to" value="{{ request('date_to') }}">
        </div>
        <button type="submit" class="btn btn-secondary btn-sm">Filtrar</button>
        <a href="{{ route('admin.conversations.index') }}" class="btn btn-secondary btn-sm">Limpar</a>
    </form>

    <table>
        <thead>
            <tr>
                <th>UUID</th>
                <th>Tenant</th>
                <th>Origem</th>
                <th>Status</th>
                <th>Mensagens</th>
                <th>IP</th>
                <th>Data</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($conversations as $conversation)
                <tr>
                    <td><code style="font-size:.75rem">{{ Str::limit($conversation->uuid, 13) }}</code></td>
                    <td>{{ $conversation->tenant?->name ?? '—' }}</td>
                    <td>{{ $conversation->source }}</td>
                    <td>
                        <span class="badge {{ $conversation->status === 'active' ? 'badge-green' : 'badge-gray' }}">
                            {{ $conversation->status }}
                        </span>
                    </td>
                    <td>{{ $conversation->messages_count }}</td>
                    <td>{{ $conversation->ip_address ?? '—' }}</td>
                    <td>{{ $conversation->created_at->format('d/m/Y H:i') }}</td>
                    <td><a href="{{ route('admin.conversations.show', $conversation) }}">Ver</a></td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="color:#64748b">Nenhuma conversa encontrada.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($conversations->hasPages())
        <div class="pagination">{{ $conversations->links() }}</div>
    @endif
</div>
@endsection
