@extends('layouts.app')

@section('title', 'Registrar movimentação — Sentinel')

@push('styles')
    @vite('resources/css/pages/movimentacoes-form.css')
@endpush

@section('content')
    <div class="page-head">
        <div>
            <h1>Registrar movimentação</h1>
            <p class="muted">Registre uma compra ou venda de ativo. O total, o preço médio e o lucro realizado são calculados automaticamente.</p>
        </div>
    </div>

    @if (session('sucesso'))
        <div class="alert alert--ok">{{ session('sucesso') }}</div>
    @endif

    <form class="card mov-card" method="POST" action="{{ route('painel.movimentacoes.store') }}">
        @csrf

        @include('movimentacoes._form', ['m' => []])

        <div class="form-actions">
            <a href="{{ route('painel.carteira') }}" class="btn-ghost">Ver carteira</a>
            <button type="submit" class="btn-primary">Registrar movimentação</button>
        </div>
    </form>
@endsection

@push('scripts')
    @vite('resources/js/pages/movimentacoes-form.js')
@endpush
