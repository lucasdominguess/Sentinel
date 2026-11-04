@extends('layouts.app')

@section('title', 'Registrar aporte — Sentinel')

@push('styles')
    @vite('resources/css/pages/aportes-registrar.css')
@endpush

@section('content')
    <div class="page-head">
        <div>
            <h1>Registrar aporte</h1>
            <p class="muted">Registre uma compra de ativo. O total investido e o preço médio da carteira são calculados automaticamente.</p>
        </div>
    </div>

    @if (session('sucesso'))
        <div class="alert alert--ok">{{ session('sucesso') }}</div>
    @endif

    <form class="card aporte-card" method="POST" action="{{ route('painel.aportes.store') }}">
        @csrf

        <label class="field field--ativo">
            <span class="field__label">Ativo</span>
            <select name="acao_id" id="aporte-ativo" required>
                <option value="" data-preco="">Selecione…</option>
                @foreach ($ativos as $a)
                    <option value="{{ $a['id'] }}" data-preco="{{ $a['preco'] }}" @selected(old('acao_id') == $a['id'])>{{ $a['ticker'] }}{{ $a['nome'] ? ' — ' . $a['nome'] : '' }}</option>
                @endforeach
            </select>
            @error('acao_id')
                <span class="field__error">{{ $message }}</span>
            @enderror
        </label>

        <div class="aporte-grid">
            <label class="field">
                <span class="field__label">Preço pago (R$)</span>
                <input type="number" name="preco_pago" id="aporte-preco" step="0.01" min="0" value="{{ old('preco_pago') }}" placeholder="0,00" inputmode="decimal" required>
                @error('preco_pago')
                    <span class="field__error">{{ $message }}</span>
                @enderror
            </label>

            <label class="field">
                <span class="field__label">Quantidade</span>
                <input type="number" name="quantidade" id="aporte-qtd" step="1" min="1" value="{{ old('quantidade') }}" placeholder="0" inputmode="numeric" required>
                @error('quantidade')
                    <span class="field__error">{{ $message }}</span>
                @enderror
            </label>

            <label class="field">
                <span class="field__label">Data da compra</span>
                <input type="date" name="data" id="aporte-data" value="{{ old('data', date('Y-m-d')) }}" max="{{ date('Y-m-d') }}">
                @error('data')
                    <span class="field__error">{{ $message }}</span>
                @enderror
            </label>
        </div>

        <div class="aporte-total">
            <span class="field__label">Total investido</span>
            <strong id="aporte-total-valor">R$ 0,00</strong>
        </div>

        <div class="form-actions">
            <a href="{{ route('painel.aportes.meus') }}" class="btn-ghost">Ver meus ativos</a>
            <button type="submit" class="btn-primary">Registrar aporte</button>
        </div>
    </form>
@endsection

@push('scripts')
    @vite('resources/js/pages/aportes-registrar.js')
@endpush
