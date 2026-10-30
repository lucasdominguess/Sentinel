{{-- $p: ticker, nome, classe ('Ação'|'FII'|'ETF'), quantidade, preco_medio, preco_atual, total_investido, valor_atual, cor (var(--cat-n)), peso (%) --}}
@props(['p'])
@php
  $brl = fn ($v) => number_format($v, 2, ',', '.');
  $val = $p->valor_atual - $p->total_investido;
  $pct = $p->total_investido > 0 ? $val / $p->total_investido * 100 : 0;
  $trend = $val > 0.004 ? 'up' : ($val < -0.004 ? 'down' : 'flat');
  $sinal = ['up' => '+', 'down' => '−', 'flat' => ''][$trend];
@endphp
<article class="card card--interactive holding-card" data-ticker="{{ $p->ticker }}">
  <div class="holding-card__head">
    <div class="holding-card__id">
      <div class="holding-card__line">
        <span class="swatch swatch--sm" style="--c: {{ $p->cor }}" aria-hidden="true"></span>
        <a class="card__link ticker" href="{{ route('aportes.ativo', $p->ticker) }}">{{ $p->ticker }}<small>.SA</small></a>
        <span class="tag tag--class">{{ $p->classe }}</span>
      </div>
      <div class="asset-card__name" title="{{ $p->nome }}">{{ $p->nome }}</div>
    </div>
    <x-badge-var :pct="$pct" />
  </div>

  <div class="holding-card__value">
    <span class="label">Valor atual</span>
    <div class="holding-card__value-row">
      <div class="price"><span class="price__cur">R$</span><span class="price__val">{{ $brl($p->valor_atual) }}</span></div>
      <span class="delta tone-{{ $trend }}">{{ $sinal }}R$ {{ $brl(abs($val)) }}</span>
    </div>
  </div>

  <x-cost-bar :cost="$p->total_investido" :value="$p->valor_atual"
              left="Investido R$ {{ $brl($p->total_investido) }}"
              right="Peso {{ number_format($p->peso, 1, ',', '.') }}%" />

  <dl class="holding-card__foot">
    <div><dt>Qtd.</dt><dd>{{ number_format($p->quantidade, 0, ',', '.') }}</dd></div>
    <div><dt>Preço médio</dt><dd>R$ {{ $brl($p->preco_medio) }}</dd></div>
    <div><dt>Preço atual</dt><dd>R$ {{ $brl($p->preco_atual) }}</dd></div>
  </dl>
</article>
