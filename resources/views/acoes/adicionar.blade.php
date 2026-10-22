@extends('layouts.app')

@section('title', 'Adicionar ticket — Sentinel')

@push('styles')
    @vite('resources/css/pages/acoes-adicionar.css')
@endpush

@section('content')
    <a href="{{ route('painel.index') }}" class="back-link">← Voltar</a>

    <div class="form-head">
        <h1>Adicionar ticket</h1>
        <p class="muted">O ativo entra na lista de monitorados; a cotação é buscada no próximo update do coletor.</p>
    </div>

    @if (session('sucesso'))
        <div class="alert alert--ok">{{ session('sucesso') }}</div>
    @endif

    <form class="card form-card" method="POST" action="{{ route('painel.adicionar.store') }}">
        @csrf

        <label class="field">
            <span class="field__label">Ticker</span>
            <input type="text" name="ticker" value="{{ old('ticker') }}" placeholder="VALE3.SA" autocomplete="off" autofocus required>
            @error('ticker')
                <span class="field__error">{{ $message }}</span>
            @enderror
        </label>

        <label class="field">
            <span class="field__label">Tipo</span>
            <select name="tipo" required>
                @foreach (\App\Enums\TipoAtivo::cases() as $t)
                    <option value="{{ $t->value }}" @selected(old('tipo') === $t->value)>{{ $t->label() }}</option>
                @endforeach
            </select>
            @error('tipo')
                <span class="field__error">{{ $message }}</span>
            @enderror
        </label>

        <div class="form-actions">
            <button type="submit" class="btn-primary">Adicionar</button>
        </div>
    </form>
@endsection
