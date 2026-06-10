@extends('admin.layout')

@section('title', 'Tenants')

@section('content')
<div class="page-header">
    <h2>Tenants</h2>
    <a href="{{ route('admin.tenants.create') }}" class="btn btn-primary">Novo tenant</a>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>Nome</th>
                <th>Slug</th>
                <th>Status</th>
                <th>API Key</th>
                <th>Conversas</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($tenants as $tenant)
                <tr>
                    <td>{{ $tenant->name }}</td>
                    <td><code>{{ $tenant->slug }}</code></td>
                    <td>
                        <span class="badge {{ $tenant->is_active ? 'badge-green' : 'badge-red' }}">
                            {{ $tenant->is_active ? 'Ativo' : 'Inativo' }}
                        </span>
                    </td>
                    <td>{{ $tenant->api_key_prefix ? $tenant->api_key_prefix.'…' : '—' }}</td>
                    <td>{{ $tenant->conversations_count }}</td>
                    <td class="actions">
                        <a href="{{ route('admin.tenants.edit', $tenant) }}" class="btn btn-secondary btn-sm">Editar</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="color:#64748b">Nenhum tenant cadastrado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($tenants->hasPages())
        <div class="pagination">{{ $tenants->links() }}</div>
    @endif
</div>
@endsection
