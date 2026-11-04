{{-- Linha da lista densa (≥1024px) do monitor. Mesmo array do <x-asset-card>:
     ['id', 'ticker', 'nome', 'ultima_cotacao' => [...], 'serie' => [...]]. --}}
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

  $temFaixa = $c && $min !== null && $max !== null && $abertura !== null
      && $max > $min && $min > 0 && $abertura > 0;

  $serie = $a['serie'] ?? [];
  if ($serie && $abertura > 0) {
      array_unshift($serie, $abertura);
  }

  $brl  = fn ($v) => number_format((float) $v, 2, ',', '.');
  $stat = fn ($v) => ($v === null || (float) $v == 0.0) ? '—' : number_format((float) $v, 2, ',', '.');
@endphp
<div class="asset-row" role="row"
     data-ticker="{{ $a['ticker'] }}"
     data-search="{{ \Illuminate\Support\Str::lower($a['ticker'] . ' ' . ($a['nome'] ?? '')) }}"
     data-variacao="{{ $pct ?? '' }}"
     data-preco="{{ $preco ?? '' }}">
  <div role="cell" class="asset-row__id">
    <a class="card__link ticker" href="{{ route('painel.show', $a['id']) }}"><x-ticker :value="$a['ticker']" /></a>
    <div class="asset-row__name" title="{{ $a['nome'] ?? '' }}">{{ $a['nome'] ?? '—' }}</div>
  </div>
  <div role="cell">
    @if ($temFaixa)
      <x-sparkline class="spark--sm" :points="$serie" :min="$min" :max="$max" :prev="$fechAnt" :trend="$trend" />
    @endif
  </div>
  <span role="cell" class="r asset-row__price">{{ $preco !== null ? $brl($preco) : '—' }}</span>
  <span role="cell" class="r delta tone-{{ $trend }}">{{ $varRs !== null ? $sinal . 'R$ ' . $brl(abs($varRs)) : '—' }}</span>
  <span role="cell" class="r">
    @if ($pct !== null)
      <x-badge-var :pct="$pct" />
    @else
      <span class="badge flat">—</span>
    @endif
  </span>
  <div role="cell">
    @if ($temFaixa)
      <x-range-bar compact :min="$min" :max="$max" :now="$preco" :open="$abertura" :trend="$trend" />
    @endif
  </div>
  <span role="cell" class="r muted">{{ $stat($fechAnt) }}</span>
  <span role="cell" class="r muted">{{ $stat($abertura) }}</span>
</div>
