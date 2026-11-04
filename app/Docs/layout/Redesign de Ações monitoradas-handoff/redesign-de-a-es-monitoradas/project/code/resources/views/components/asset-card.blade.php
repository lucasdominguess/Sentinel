@props(['a'])
@php
  $trend = $a->variacao > 0 ? 'up' : ($a->variacao < 0 ? 'down' : 'flat');
  $brl = fn ($v) => number_format($v, 2, ',', '.');
  $sinal = $trend === 'up' ? '+' : ($trend === 'down' ? '−' : '');
@endphp
<article class="card card--interactive asset-card" data-ticker="{{ $a->ticker }}" data-search="{{ \Illuminate\Support\Str::lower($a->ticker . ' ' . $a->nome) }}">
  <div class="asset-card__head">
    <div class="asset-card__id">
      <a class="card__link ticker" href="{{ route('acoes.show', $a->ticker) }}">{{ $a->ticker }}<small>.SA</small></a>
      <div class="asset-card__name" title="{{ $a->nome }}">{{ $a->nome }}</div>
    </div>
    <x-badge-var :pct="$a->variacao_pct" />
  </div>

  <div class="asset-card__price">
    <div class="price"><span class="price__cur">R$</span><span class="price__val">{{ $brl($a->preco) }}</span></div>
    <span class="delta tone-{{ $trend }}">{{ $sinal }}R$ {{ $brl(abs($a->variacao)) }}</span>
  </div>

  <x-sparkline :points="$a->serie" :min="$a->min" :max="$a->max" :prev="$a->fech_ant" :trend="$trend" />

  <x-range-bar :min="$a->min" :max="$a->max" :now="$a->preco" :open="$a->abertura" :trend="$trend" />

  <dl class="asset-card__foot">
    <div><dt>Fech. ant.</dt><dd>R$ {{ $brl($a->fech_ant) }}</dd></div>
    <div><dt>Abertura</dt><dd>R$ {{ $brl($a->abertura) }}</dd></div>
  </dl>
</article>
