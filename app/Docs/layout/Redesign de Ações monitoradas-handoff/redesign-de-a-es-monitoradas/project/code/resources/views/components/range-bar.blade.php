{{-- Faixa do dia: trilho mín→máx, preenchimento abertura→atual, tick de abertura, marcador do preço atual. --}}
@props(['min', 'max', 'now', 'open', 'trend' => 'flat', 'compact' => false])
@php
  $span = max($max - $min, 1e-9);
  $pos = round(($now - $min) / $span * 100, 1);
  $openPos = round(($open - $min) / $span * 100, 1);
  $brl = fn ($v) => number_format($v, 2, ',', '.');
@endphp
<div class="range range--{{ $trend }}" role="img"
     aria-label="Faixa do dia de R$ {{ $brl($min) }} a R$ {{ $brl($max) }}; abertura R$ {{ $brl($open) }}; atual R$ {{ $brl($now) }}">
  @if ($compact) <span>{{ $brl($min) }}</span> @endif
  <div class="range__track">
    <span class="range__fill" style="left: {{ min($pos, $openPos) }}%; width: {{ abs($pos - $openPos) }}%"></span>
    @unless ($compact) <span class="range__open" style="left: {{ $openPos }}%" title="Abertura"></span> @endunless
    <span class="range__now" style="left: {{ $pos }}%"></span>
  </div>
  @if ($compact)
    <span>{{ $brl($max) }}</span>
  @else
    <div class="range__labels"><span>Mín. R$ {{ $brl($min) }}</span><span>Máx. R$ {{ $brl($max) }}</span></div>
  @endif
</div>
