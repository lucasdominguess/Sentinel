@extends('layouts.app')

@section('title', $ativo['ticker'] . ' — Detalhes')

@push('styles')
    @vite('resources/css/pages/acoes-detalhes.css')
@endpush

@section('content')
    @php
        $tipoLabel = \App\Enums\TipoAtivo::tryFrom($ativo['tipo'])?->label() ?? $ativo['tipo'];
        $c = $ativo['ultima_cotacao'] ?? null;
        $pct = $c && $c['variacao_dia_pct'] !== null ? (float) $c['variacao_dia_pct'] : null;
        $pctCls = $pct === null ? '' : ($pct > 0 ? 'up' : ($pct < 0 ? 'down' : ''));
        $fmtBrl = fn($v) => $v === null ? '—' : 'R$ ' . number_format((float) $v, 2, ',', '.');
        $fmtDt = fn($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('d/m/Y H:i') : '—';
    @endphp

    <a href="{{ route('painel.ativos.index') }}" class="back-link">← Voltar ao cadastro</a>

    <div class="det-head">
        <div>
            <div class="det-ticker">
                <h1>{{ $ativo['ticker'] }}</h1>
                @if ($ativo['ativo'])
                    <span class="badge badge--up">Ativo</span>
                @else
                    <span class="badge badge--off">Inativo</span>
                @endif
            </div>
            <p class="muted">{{ $ativo['nome'] ?? 'Sem nome cadastrado' }}</p>
        </div>
        <div class="det-actions">
            <a href="{{ route('painel.show', $ativo['id']) }}" class="btn-ghost">Ver cotações</a>
            <a href="{{ route('painel.ativos.edit', $ativo['id']) }}" class="btn-primary">Editar</a>
        </div>
    </div>

    <div class="card det-card">
        <h2 class="det-section">Dados cadastrais</h2>
        <dl class="det-grid">
            <div class="det-item">
                <dt>ID</dt>
                <dd>#{{ $ativo['id'] }}</dd>
            </div>
            <div class="det-item">
                <dt>Tipo</dt>
                <dd><span class="tag">{{ $tipoLabel }}</span></dd>
            </div>
            <div class="det-item">
                <dt>Setor</dt>
                <dd>{{ $ativo['setor'] ?? '—' }}</dd>
            </div>
            <div class="det-item">
                <dt>Segmento</dt>
                <dd>{{ $ativo['segmento'] ?? '—' }}</dd>
            </div>
            <div class="det-item">
                <dt>Moeda</dt>
                <dd>{{ $ativo['moeda'] ?? '—' }}</dd>
            </div>
            <div class="det-item">
                <dt>Status</dt>
                <dd>{{ $ativo['ativo'] ? 'Ativo (monitorado)' : 'Inativo' }}</dd>
            </div>
            <div class="det-item">
                <dt>Cadastrado em</dt>
                <dd>{{ $fmtDt($criadoEm) }}</dd>
            </div>
            <div class="det-item">
                <dt>Atualizado em</dt>
                <dd>{{ $fmtDt($atualizadoEm) }}</dd>
            </div>
        </dl>
    </div>

    <div class="card det-card">
        <h2 class="det-section">Última cotação</h2>
        @if ($c)
            <dl class="det-grid">
                <div class="det-item">
                    <dt>Preço</dt>
                    <dd class="forte">{{ $fmtBrl($c['preco']) }}</dd>
                </div>
                <div class="det-item">
                    <dt>Variação no dia</dt>
                    <dd class="{{ $pctCls }}">{{ $pct !== null ? number_format($pct, 2, ',', '.') . '%' : '—' }}</dd>
                </div>
                <div class="det-item">
                    <dt>Abertura</dt>
                    <dd>{{ $fmtBrl($c['abertura']) }}</dd>
                </div>
                <div class="det-item">
                    <dt>Fech. anterior</dt>
                    <dd>{{ $fmtBrl($c['fechamento_anterior']) }}</dd>
                </div>
                <div class="det-item">
                    <dt>Mín. / Máx. dia</dt>
                    <dd>{{ $fmtBrl($c['minima_dia']) }} / {{ $fmtBrl($c['maxima_dia']) }}</dd>
                </div>
                <div class="det-item">
                    <dt>Capturado em</dt>
                    <dd>{{ $fmtDt($c['capturado_em']) }}</dd>
                </div>
            </dl>
        @else
            <p class="muted">Nenhuma cotação coletada ainda para este ativo.</p>
        @endif
    </div>
@endsection
