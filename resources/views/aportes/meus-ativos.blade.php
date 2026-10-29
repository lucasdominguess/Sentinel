@extends('layouts.app')

@section('title', 'Meus ativos — Sentinel')

@push('styles')
    @vite('resources/css/pages/aportes-meus.css')
@endpush

@section('content')
    @php
        $fmtBrl = fn($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
        $fmtPct = fn($v) => number_format((float) $v, 2, ',', '.') . '%';

        $investidoTotal = $posicoes->sum('total_investido');
        $valorAtualTotal = $posicoes->sum(fn($p) => $p['valor_atual'] ?? $p['total_investido']);
        $valorizacaoTotal = $valorAtualTotal - $investidoTotal;
        $valorizacaoPctTotal = $investidoTotal > 0 ? ($valorizacaoTotal / $investidoTotal) * 100 : null;
        $clsTotal = $valorizacaoTotal > 0 ? 'up' : ($valorizacaoTotal < 0 ? 'down' : 'flat');
    @endphp

    <div class="page-head">
        <div>
            <h1>Meus ativos</h1>
            <p class="muted">Posição consolidada da carteira — preço médio pago e valorização com base na última cotação.</p>
        </div>
    </div>

    @if ($posicoes->isEmpty())
        <div class="card empty">
            <p>Nenhum aporte registrado ainda.</p>
            <p class="muted">Use <a href="{{ route('painel.aportes.create') }}">Registrar aporte</a> para começar a montar sua carteira.</p>
        </div>
    @else
        <div class="resumo">
            <div class="card resumo__item">
                <span class="k">Total investido</span>
                <strong class="v">{{ $fmtBrl($investidoTotal) }}</strong>
            </div>
            <div class="card resumo__item">
                <span class="k">Valor atual</span>
                <strong class="v">{{ $fmtBrl($valorAtualTotal) }}</strong>
            </div>
            <div class="card resumo__item">
                <span class="k">Valorização</span>
                <strong class="v valor--{{ $clsTotal }}">
                    {{ $valorizacaoTotal >= 0 ? '+' : '−' }}{{ $fmtBrl(abs($valorizacaoTotal)) }}
                    @if ($valorizacaoPctTotal !== null)
                        <span class="badge badge--{{ $clsTotal === 'flat' ? 'flat' : ($clsTotal === 'up' ? 'up' : 'down') }}">{{ $valorizacaoPctTotal >= 0 ? '▲' : '▼' }} {{ $fmtPct(abs($valorizacaoPctTotal)) }}</span>
                    @endif
                </strong>
            </div>
        </div>

        <div class="pos-grid">
            @foreach ($posicoes as $p)
                @php
                    $temCotacao = $p['preco_atual'] !== null;
                    $valPct = $p['valorizacao_pct'];
                    $valRs = $p['valorizacao_reais'];
                    $cls = !$temCotacao ? 'flat' : ($valRs > 0 ? 'up' : ($valRs < 0 ? 'down' : 'flat'));
                    $arrow = $cls === 'up' ? '▲' : ($cls === 'down' ? '▼' : '•');
                @endphp
                <div class="card pos-card">
                    <div class="pos-card__top">
                        <span class="pos-card__ticker">{{ $p['ticker'] ?? '—' }}</span>
                        <span class="badge badge--{{ $cls }}">
                            {{ $arrow }} {{ $temCotacao && $valPct !== null ? $fmtPct(abs($valPct)) : '—' }}
                        </span>
                    </div>
                    <div class="pos-card__nome">{{ $p['nome'] ?? '—' }}</div>

                    <div class="pos-card__medio">
                        <span class="k">Preço médio</span>
                        <span class="v">{{ $fmtBrl($p['preco_medio']) }}</span>
                    </div>

                    <div class="pos-card__stats">
                        <div><span class="k">Quantidade</span><span class="v">{{ number_format($p['quantidade'], 0, ',', '.') }}</span></div>
                        <div><span class="k">Total investido</span><span class="v">{{ $fmtBrl($p['total_investido']) }}</span></div>
                        <div><span class="k">Preço atual</span><span class="v">{{ $temCotacao ? $fmtBrl($p['preco_atual']) : '—' }}</span></div>
                        <div><span class="k">Valor atual</span><span class="v">{{ $temCotacao ? $fmtBrl($p['valor_atual']) : '—' }}</span></div>
                    </div>

                    <div class="pos-card__val valor--{{ $cls }}">
                        <span class="k">Valorização</span>
                        <span class="v">{{ $temCotacao ? (($valRs >= 0 ? '+' : '−') . $fmtBrl(abs($valRs))) : 'sem cotação' }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
