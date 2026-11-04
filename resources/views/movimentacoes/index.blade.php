@extends('layouts.app')

@section('title', 'Movimentações — Sentinel')

@push('styles')
    @vite('resources/css/pages/movimentacoes-index.css')
@endpush

@section('content')
    @php
        $fmtBrl = fn($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
    @endphp

    <div class="page-head">
        <div>
            <h1>Movimentações</h1>
            <p class="muted">{{ $movimentacoes->total() }} movimentação(ões) registrada(s) — compras e vendas da carteira.</p>
        </div>
        <a href="{{ route('painel.movimentacoes.create') }}" class="btn-primary">+ Registrar movimentação</a>
    </div>

    @if (session('sucesso'))
        <div class="alert alert--ok">{{ session('sucesso') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert--erro">{{ $errors->first() }}</div>
    @endif

    @if ($movimentacoes->isEmpty())
        <div class="card empty">
            <p>Nenhuma movimentação registrada ainda.</p>
            <p class="muted">Use <a href="{{ route('painel.movimentacoes.create') }}">Registrar movimentação</a> para adicionar sua primeira compra.</p>
        </div>
    @else
        <div class="card table-wrap">
            <table class="reg-table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Tipo</th>
                        <th class="reg-ativo">Ativo</th>
                        <th class="num">Preço</th>
                        <th class="num">Qtd.</th>
                        <th class="num">Total</th>
                        <th class="acoes-col">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($movimentacoes as $a)
                        @php $venda = $a['tipo'] === \App\Enums\TipoMovimentacao::VENDA->value; @endphp
                        <tr>
                            <td>{{ \Illuminate\Support\Carbon::parse($a['data'])->format('d/m/Y') }}</td>
                            <td><span class="badge {{ $venda ? 'badge--down' : 'badge--up' }}">{{ \App\Enums\TipoMovimentacao::from($a['tipo'])->label() }}</span></td>
                            <td class="reg-ativo">
                                <span class="reg-ticker">{{ $a['ticker'] ?? '—' }}</span>
                                @if ($a['nome'])
                                    <span class="reg-nome muted" title="{{ $a['nome'] }}">{{ $a['nome'] }}</span>
                                @endif
                            </td>
                            <td class="num">{{ $fmtBrl($a['preco']) }}</td>
                            <td class="num">{{ $venda ? '−' : '' }}{{ number_format($a['quantidade'], 0, ',', '.') }}</td>
                            <td class="num forte">{{ $fmtBrl($a['total']) }}</td>
                            <td class="acoes-col">
                                <div class="reg-acoes">
                                    <a href="{{ route('painel.movimentacoes.edit', $a['id']) }}" class="btn-acao">Editar</a>
                                    <form method="POST" action="{{ route('painel.movimentacoes.destroy', $a['id']) }}"
                                          onsubmit="return confirm('Excluir esta {{ $venda ? 'venda' : 'compra' }} de {{ $a['ticker'] ?? 'ativo' }}? Esta ação não pode ser desfeita.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-acao btn-acao--danger">Excluir</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($movimentacoes->lastPage() > 1)
            <nav class="pager" aria-label="Paginação">
                @if ($movimentacoes->previousPageUrl())
                    <a class="pager__btn" href="{{ $movimentacoes->previousPageUrl() }}" rel="prev">← Anterior</a>
                @else
                    <span class="pager__btn is-disabled">← Anterior</span>
                @endif

                <span class="pager__info">Página {{ $movimentacoes->currentPage() }} de {{ $movimentacoes->lastPage() }}</span>

                @if ($movimentacoes->nextPageUrl())
                    <a class="pager__btn" href="{{ $movimentacoes->nextPageUrl() }}" rel="next">Próxima →</a>
                @else
                    <span class="pager__btn is-disabled">Próxima →</span>
                @endif
            </nav>
        @endif
    @endif
@endsection
