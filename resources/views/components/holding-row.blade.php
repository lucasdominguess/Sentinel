{{-- Linha da lista densa (≥1024px) da carteira. Mesmo array do <x-holding-card>
     + $cor (var(--cat-n)) e $peso (%) calculados na view. A barra de peso satura em $maxPeso. --}}
@props(['p', 'cor' => 'var(--cat-rest)', 'peso' => 0, 'maxPeso' => 60])
@php
  $brl = fn ($v) => number_format((float) $v, 2, ',', '.');
  $temCotacao = ($p['preco_atual'] ?? null) !== null;
  $valorAtual = $p['valor_atual'] ?? $p['total_investido'];
  $val = $temCotacao ? (float) $p['valorizacao_reais'] : 0.0;
  $pct = $temCotacao ? (float) ($p['valorizacao_pct'] ?? 0) : 0.0;
  $trend = $val > 0.004 ? 'up' : ($val < -0.004 ? 'down' : 'flat');
  $sinal = ['up' => '+', 'down' => '−', 'flat' => ''][$trend];
  $classe = \App\Enums\TipoAtivo::tryFrom((string) ($p['tipo'] ?? ''))?->label();
@endphp
<div class="asset-row holding-row" role="row"
     data-ticker="{{ $p['ticker'] }}"
     data-tipo="{{ $p['tipo'] ?? '' }}"
     data-posicao="{{ $valorAtual }}"
     data-valorizacao="{{ $temCotacao ? ($p['valorizacao_pct'] ?? '') : '' }}">
  <div role="cell" class="holding-row__id">
    <span class="swatch swatch--sm" style="--c: {{ $cor }}" aria-hidden="true"></span>
    <div style="min-width:0">
      <a class="card__link ticker" href="{{ route('painel.show', $p['acao_id']) }}"><x-ticker :value="$p['ticker']" /></a>
      <div class="asset-row__name" title="{{ $p['nome'] ?? '' }}">{{ $p['nome'] ?? '—' }}</div>
    </div>
  </div>
  <span role="cell">@if ($classe)<span class="tag tag--class">{{ $classe }}</span>@endif</span>
  <span role="cell" class="r muted">{{ number_format($p['quantidade'], 0, ',', '.') }}</span>
  <span role="cell" class="r muted">R$ {{ $brl($p['preco_medio']) }}</span>
  <span role="cell" class="r muted">{{ $temCotacao ? 'R$ ' . $brl($p['preco_atual']) : '—' }}</span>
  <span role="cell" class="r muted">{{ $brl($p['total_investido']) }}</span>
  <span role="cell" class="r holding-row__now">{{ $brl($valorAtual) }}</span>
  <span role="cell" class="r holding-row__val tone-{{ $trend }}">
    @if ($temCotacao)
      {{ $sinal }}R$ {{ $brl(abs($val)) }} <x-badge-var :pct="$pct" />
    @else
      <span class="price__none">sem cotação</span>
    @endif
  </span>
  <div role="cell" class="weight" style="--c: {{ $cor }}">
    <div class="weight__track"><span class="weight__fill" style="width: {{ round(min(100, $peso / $maxPeso * 100), 1) }}%"></span></div>
    <span class="weight__pct">{{ number_format($peso, 1, ',', '.') }}%</span>
  </div>
</div>
