@extends('admin.layout')

@section('title', 'Novo tenant')

@section('content')
<div class="page-header">
    <h2>Novo tenant</h2>
    <a href="{{ route('admin.tenants.index') }}" class="btn btn-secondary">Voltar</a>
</div>

<div class="card">
    <form method="POST" action="{{ route('admin.tenants.store') }}">
        @csrf
        @include('admin.tenants._form')
        <button type="submit" class="btn btn-primary">Criar tenant</button>
    </form>
</div>
@endsection
