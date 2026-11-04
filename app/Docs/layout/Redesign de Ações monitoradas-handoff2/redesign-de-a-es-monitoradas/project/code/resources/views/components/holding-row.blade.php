@props(['p', 'maxPeso' => 60])
@php
  $brl = fn ($v) => number_format($v, 2, ',', '.');
  $val = $p->valor_atual - $p->total_investido;
  $pct = $p->total_investido > 0 ? $val / $p->total_investido * 100 : 0;
  $trend = $val > 0.004 ? 'up' : ($val < -0.004 ? 'down' : 'flat');
  $sinal = ['up' => '+', 'down' => '−', 'flat' => ''][$trend];
@endphp
<div class="asset-row holding-row" role="row" data-ticker="{{ $p->ticker }}">
  <div role="cell" class="holding-row__id">
    <span class="swatch swatch--sm" style="--c: {{ $p->cor }}" aria-hidden="true"></span>
    <div style="min-width:0">
      <a class="card__link ticker" href="{{ route('aportes.ativo', $p->ticker) }}">{{ $p->ticker }}<small>.SA</small></a>
      <div class="asset-card__name" title="{{ $p->nome }}">{{ $p->nome }}</div>
    </div>
  </div>
  <span role="cell"><span class="tag tag--class">{{ $p->classe }}</span></span>
  <span role="cell" class="r muted">{{ number_format($p->quantidade, 0, ',', '.') }}</span>
  <span role="cell" class="r muted">R$ {{ $brl($p->preco_medio) }}</span>
  <span role="cell" class="r muted">R$ {{ $brl($p->preco_atual) }}</span>
  <span role="cell" class="r muted">{{ $brl($p->total_investido) }}</span>
  <span role="cell" class="r holding-row__now">{{ $brl($p->valor_atual) }}</span>
  <span role="cell" class="r holding-row__val tone-{{ $trend }}">{{ $sinal }}R$ {{ $brl(abs($val)) }} <x-badge-var :pct="$pct" /></span>
  <div role="cell" class="weight" style="--c: {{ $p->cor }}">
    <div class="weight__track"><span class="weight__fill" style="width: {{ min(100, $p->peso / $maxPeso * 100) }}%"></span></div>
    <span class="weight__pct">{{ number_format($p->peso, 1, ',', '.') }}%</span>
  </div>
</div>
