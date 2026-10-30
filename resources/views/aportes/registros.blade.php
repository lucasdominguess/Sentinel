@extends('layouts.app')

@section('title', 'Registros de aportes — Sentinel')

@push('styles')
    @vite('resources/css/pages/aportes-registros.css')
@endpush

@section('content')
    @php
        $fmtBrl = fn($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
    @endphp

    <div class="page-head">
        <div>
            <h1>Registros de aportes</h1>
            <p class="muted">{{ $aportes->total() }} aporte(s) registrado(s).</p>
        </div>
        <a href="{{ route('painel.aportes.create') }}" class="btn-primary">+ Registrar aporte</a>
    </div>

    @if (session('sucesso'))
        <div class="alert alert--ok">{{ session('sucesso') }}</div>
    @endif

    @if ($aportes->isEmpty())
        <div class="card empty">
            <p>Nenhum aporte registrado ainda.</p>
            <p class="muted">Use <a href="{{ route('painel.aportes.create') }}">Registrar aporte</a> para adicionar sua primeira compra.</p>
        </div>
    @else
        <div class="card table-wrap">
            <table class="reg-table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Ativo</th>
                        <th class="num">Preço pago</th>
                        <th class="num">Qtd.</th>
                        <th class="num">Total investido</th>
                        <th class="acoes-col">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($aportes as $a)
                        <tr>
                            <td>{{ \Illuminate\Support\Carbon::parse($a['data'])->format('d/m/Y') }}</td>
                            <td>
                                <span class="reg-ticker">{{ $a['ticker'] ?? '—' }}</span>
                                @if ($a['nome'])
                                    <span class="reg-nome muted">{{ $a['nome'] }}</span>
                                @endif
                            </td>
                            <td class="num">{{ $fmtBrl($a['preco_pago']) }}</td>
                            <td class="num">{{ number_format($a['quantidade'], 0, ',', '.') }}</td>
                            <td class="num forte">{{ $fmtBrl($a['total_investido']) }}</td>
                            <td class="acoes-col">
                                <div class="reg-acoes">
                                    <a href="{{ route('painel.aportes.edit', $a['id']) }}" class="btn-acao">Editar</a>
                                    <form method="POST" action="{{ route('painel.aportes.destroy', $a['id']) }}"
                                          onsubmit="return confirm('Excluir este aporte de {{ $a['ticker'] ?? 'ativo' }}? Esta ação não pode ser desfeita.');">
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

        @if ($aportes->lastPage() > 1)
            <nav class="pager" aria-label="Paginação">
                @if ($aportes->previousPageUrl())
                    <a class="pager__btn" href="{{ $aportes->previousPageUrl() }}" rel="prev">← Anterior</a>
                @else
                    <span class="pager__btn is-disabled">← Anterior</span>
                @endif

                <span class="pager__info">Página {{ $aportes->currentPage() }} de {{ $aportes->lastPage() }}</span>

                @if ($aportes->nextPageUrl())
                    <a class="pager__btn" href="{{ $aportes->nextPageUrl() }}" rel="next">Próxima →</a>
                @else
                    <span class="pager__btn is-disabled">Próxima →</span>
                @endif
            </nav>
        @endif
    @endif
@endsection
