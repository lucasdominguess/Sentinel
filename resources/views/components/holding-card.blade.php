{{-- Card de posição da carteira. Recebe o array real de PosicaoResponseDTO::toArray():
     ['acao_id','ticker','nome','quantidade','total_investido','preco_medio','preco_atual','valor_atual','valorizacao_reais','valorizacao_pct']
     + $cor (var(--cat-n)) e $peso (%) calculados na view. --}}
@props(['p', 'cor' => 'var(--cat-rest)', 'peso' => 0])
@php
  $brl = fn ($v) => number_format((float) $v, 2, ',', '.');
  $temCotacao = ($p['preco_atual'] ?? null) !== null;
  $valorAtual = $p['valor_atual'] ?? $p['total_investido'];
  $val = $temCotacao ? (float) $p['valorizacao_reais'] : 0.0;
  $pct = $temCotacao ? (float) ($p['valorizacao_pct'] ?? 0) : 0.0;
  $trend = $val > 0.004 ? 'up' : ($val < -0.004 ? 'down' : 'flat');
  $sinal = ['up' => '+', 'down' => '−', 'flat' => ''][$trend];
@endphp
<article class="card card--interactive holding-card"
         data-ticker="{{ $p['ticker'] }}"
         data-posicao="{{ $valorAtual }}"
         data-valorizacao="{{ $temCotacao ? ($p['valorizacao_pct'] ?? '') : '' }}">
  <div class="holding-card__head">
    <div class="holding-card__id">
      <div class="holding-card__line">
        <span class="swatch swatch--sm" style="--c: {{ $cor }}" aria-hidden="true"></span>
        <a class="card__link ticker" href="{{ route('painel.show', $p['acao_id']) }}">{{ $p['ticker'] ?? '—' }}</a>
      </div>
      <div class="holding-card__name" title="{{ $p['nome'] ?? '' }}">{{ $p['nome'] ?? '—' }}</div>
    </div>
    @if ($temCotacao)
      <x-badge-var :pct="$pct" />
    @else
      <span class="badge flat">—</span>
    @endif
  </div>

  <div class="holding-card__value">
    <span class="label">Valor atual</span>
    <div class="holding-card__value-row">
      <div class="price"><span class="price__cur">R$</span><span class="price__val">{{ $brl($valorAtual) }}</span></div>
      @if ($temCotacao)
        <span class="delta tone-{{ $trend }}">{{ $sinal }}R$ {{ $brl(abs($val)) }}</span>
      @else
        <span class="price__none">sem cotação</span>
      @endif
    </div>
  </div>

  <x-cost-bar :cost="$p['total_investido']" :value="$valorAtual"
              left="Investido R$ {{ $brl($p['total_investido']) }}"
              right="Peso {{ number_format($peso, 1, ',', '.') }}%" />

  <dl class="holding-card__foot">
    <div><dt>Qtd.</dt><dd>{{ number_format($p['quantidade'], 0, ',', '.') }}</dd></div>
    <div><dt>Preço médio</dt><dd>R$ {{ $brl($p['preco_medio']) }}</dd></div>
    <div><dt>Preço atual</dt><dd>{{ $temCotacao ? 'R$ ' . $brl($p['preco_atual']) : '—' }}</dd></div>
  </dl>
</article>
