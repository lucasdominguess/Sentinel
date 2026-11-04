@extends('layouts.app')

@section('title', 'Editar ' . $ativo['ticker'] . ' — Sentinel')

@push('styles')
    @vite('resources/css/pages/acoes-adicionar.css')
@endpush

@section('content')
    @php
        $statusAtual = (int) old('ativo', $ativo['ativo']);
    @endphp

    <a href="{{ route('painel.ativos.index') }}" class="back-link">← Voltar ao cadastro</a>

    <div class="form-head">
        <h1>Editar ativo</h1>
        <p class="muted">Ajuste os metadados do ativo. O ticker deve ser único.</p>
    </div>

    <form class="card form-card" method="POST" action="{{ route('painel.ativos.update', $ativo['id']) }}">
        @csrf
        @method('PUT')

        <label class="field">
            <span class="field__label">Ticker</span>
            <input type="text" name="ticker" value="{{ old('ticker', $ativo['ticker']) }}" placeholder="VALE3.SA" autocomplete="off" required>
            @error('ticker')
                <span class="field__error">{{ $message }}</span>
            @enderror
        </label>

        <label class="field">
            <span class="field__label">Nome</span>
            <input type="text" name="nome" value="{{ old('nome', $ativo['nome']) }}" placeholder="Vale S.A." autocomplete="off">
            @error('nome')
                <span class="field__error">{{ $message }}</span>
            @enderror
        </label>

        <label class="field">
            <span class="field__label">Setor</span>
            <input type="text" name="setor" value="{{ old('setor', $ativo['setor']) }}" placeholder="Materiais básicos" autocomplete="off">
            @error('setor')
                <span class="field__error">{{ $message }}</span>
            @enderror
        </label>

        <label class="field">
            <span class="field__label">Segmento</span>
            <input type="text" name="segmento" value="{{ old('segmento', $ativo['segmento']) }}" placeholder="Mineração" autocomplete="off">
            @error('segmento')
                <span class="field__error">{{ $message }}</span>
            @enderror
        </label>

        <label class="field">
            <span class="field__label">Moeda</span>
            <input type="text" name="moeda" value="{{ old('moeda', $ativo['moeda']) }}" placeholder="BRL" maxlength="3" autocomplete="off">
            @error('moeda')
                <span class="field__error">{{ $message }}</span>
            @enderror
        </label>

        <label class="field">
            <span class="field__label">Tipo</span>
            <select name="tipo" required>
                @foreach (\App\Enums\TipoAtivo::cases() as $t)
                    <option value="{{ $t->value }}" @selected(old('tipo', $ativo['tipo']) === $t->value)>{{ $t->label() }}</option>
                @endforeach
            </select>
            @error('tipo')
                <span class="field__error">{{ $message }}</span>
            @enderror
        </label>

        <label class="field">
            <span class="field__label">Status</span>
            <select name="ativo">
                <option value="1" @selected($statusAtual === 1)>Ativo (monitorado pelo coletor)</option>
                <option value="0" @selected($statusAtual === 0)>Inativo</option>
            </select>
            @error('ativo')
                <span class="field__error">{{ $message }}</span>
            @enderror
        </label>

        <div class="form-actions form-actions--split">
            <a href="{{ route('painel.ativos.index') }}" class="btn-ghost">Cancelar</a>
            <button type="submit" class="btn-primary">Salvar alterações</button>
        </div>
    </form>
@endsection
