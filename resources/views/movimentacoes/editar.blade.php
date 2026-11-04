@extends('layouts.app')

@section('title', 'Editar movimentação — Sentinel')

@push('styles')
    @vite('resources/css/pages/movimentacoes-form.css')
@endpush

@section('content')
    @php
        // Saldo exibido no formulário desconsidera esta movimentação: é o que sobra
        // para ela, seja qual for o tipo escolhido na edição.
        $ativos = array_map(function ($a) use ($movimentacao) {
            if ($a['id'] === $movimentacao['acao_id']) {
                $a['cotas'] += $movimentacao['tipo'] === 'venda' ? $movimentacao['quantidade'] : -$movimentacao['quantidade'];
            }
            return $a;
        }, $ativos);
    @endphp

    <div class="page-head">
        <div>
            <h1>Editar movimentação</h1>
            <p class="muted">Ajuste os dados da operação. O total é recalculado automaticamente.</p>
        </div>
    </div>

    <form class="card mov-card" method="POST" action="{{ route('painel.movimentacoes.update', $movimentacao['id']) }}">
        @csrf
        @method('PUT')

        @include('movimentacoes._form', ['m' => $movimentacao])

        <div class="form-actions">
            <a href="{{ route('painel.movimentacoes.index') }}" class="btn-ghost">Cancelar</a>
            <button type="submit" class="btn-primary">Salvar alterações</button>
        </div>
    </form>
@endsection

@push('scripts')
    @vite('resources/js/pages/movimentacoes-form.js')
@endpush
