{{-- Card de ativo monitorado. Recebe o array real vindo do controller ($acao):
     ['id', 'ticker', 'nome', 'ultima_cotacao' => [...]]. --}}
@props(['a'])
@php
  $c = $a['ultima_cotacao'] ?? null;

  $pct      = isset($c['variacao_dia_pct'])     ? (float) $c['variacao_dia_pct']     : null;
  $varRs    = isset($c['variacao_dia'])         ? (float) $c['variacao_dia']         : null;
  $preco    = isset($c['preco'])                ? (float) $c['preco']                : null;
  $min      = isset($c['minima_dia'])           ? (float) $c['minima_dia']           : null;
  $max      = isset($c['maxima_dia'])           ? (float) $c['maxima_dia']           : null;
  $abertura = isset($c['abertura'])             ? (float) $c['abertura']             : null;
  $fechAnt  = isset($c['fechamento_anterior'])  ? (float) $c['fechamento_anterior']  : null;

  $trend = $pct === null ? 'flat' : ($pct > 0 ? 'up' : ($pct < 0 ? 'down' : 'flat'));
  $sinal = $trend === 'up' ? '+' : ($trend === 'down' ? '−' : '');

  // Faixa do dia só quando mín/máx/abertura são coerentes (evita barra degenerada no pré-pregão)
  $temFaixa = $c && $min !== null && $max !== null && $abertura !== null
      && $max > $min && $min > 0 && $abertura > 0;

  $brl  = fn ($v) => number_format((float) $v, 2, ',', '.');
  $stat = fn ($v) => ($v === null || (float) $v == 0.0) ? '—' : 'R$ ' . number_format((float) $v, 2, ',', '.');
@endphp
<article class="card card--interactive asset-card"
         data-ticker="{{ $a['ticker'] }}"
         data-search="{{ \Illuminate\Support\Str::lower($a['ticker'] . ' ' . ($a['nome'] ?? '')) }}"
         data-variacao="{{ $pct ?? '' }}"
         data-preco="{{ $preco ?? '' }}"
         data-min-mes="{{ $c['minima_mes'] ?? '' }}"
         data-min-mes-data="{{ $c['minima_mes_data'] ?? '' }}"
         data-max-mes="{{ $c['maxima_mes'] ?? '' }}"
         data-max-mes-data="{{ $c['maxima_mes_data'] ?? '' }}">
  <div class="asset-card__head">
    <div class="asset-card__id">
      <a class="card__link ticker" href="{{ route('painel.show', $a['id']) }}">{{ $a['ticker'] }}</a>
      <div class="asset-card__name" title="{{ $a['nome'] ?? '' }}">{{ $a['nome'] ?? '—' }}</div>
    </div>
    @if ($pct !== null)
      <x-badge-var :pct="$pct" />
    @else
      <span class="badge flat">—</span>
    @endif
  </div>

  @if ($preco !== null)
    <div class="asset-card__price">
      <div class="price"><span class="price__cur">R$</span><span class="price__val">{{ $brl($preco) }}</span></div>
      @if ($varRs !== null)
        <span class="delta tone-{{ $trend }}">{{ $sinal }}R$ {{ $brl(abs($varRs)) }}</span>
      @endif
    </div>
  @else
    <div class="asset-card__price"><span class="price__none">Sem cotação</span></div>
  @endif

  @if ($temFaixa)
    <x-range-bar :min="$min" :max="$max" :now="$preco" :open="$abertura" :trend="$trend" />
  @endif

  @if ($c)
    <dl class="asset-card__foot">
      <div><dt>Fech. ant.</dt><dd>{{ $stat($fechAnt) }}</dd></div>
      <div><dt>Abertura</dt><dd>{{ $stat($abertura) }}</dd></div>
    </dl>
  @endif
</article>
