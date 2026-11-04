@props(['a'])
@php
  $trend = $a->variacao > 0 ? 'up' : ($a->variacao < 0 ? 'down' : 'flat');
  $brl = fn ($v) => number_format($v, 2, ',', '.');
  $sinal = $trend === 'up' ? '+' : ($trend === 'down' ? '−' : '');
@endphp
<div class="asset-row" role="row" data-ticker="{{ $a->ticker }}" data-search="{{ \Illuminate\Support\Str::lower($a->ticker . ' ' . $a->nome) }}">
  <div role="cell" style="min-width:0">
    <a class="card__link ticker" href="{{ route('acoes.show', $a->ticker) }}">{{ $a->ticker }}<small>.SA</small></a>
    <div class="asset-card__name" title="{{ $a->nome }}">{{ $a->nome }}</div>
  </div>
  <div role="cell"><x-sparkline class="spark--sm" :points="$a->serie" :min="$a->min" :max="$a->max" :prev="$a->fech_ant" :trend="$trend" /></div>
  <span role="cell" class="r price"><span class="price__val" style="font-size:15px">{{ $brl($a->preco) }}</span></span>
  <span role="cell" class="r delta tone-{{ $trend }}">{{ $sinal }}R$ {{ $brl(abs($a->variacao)) }}</span>
  <span role="cell" class="r"><x-badge-var :pct="$a->variacao_pct" /></span>
  <div role="cell"><x-range-bar compact :min="$a->min" :max="$a->max" :now="$a->preco" :open="$a->abertura" :trend="$trend" /></div>
  <span role="cell" class="r muted">{{ $brl($a->fech_ant) }}</span>
  <span role="cell" class="r muted">{{ $brl($a->abertura) }}</span>
</div>
