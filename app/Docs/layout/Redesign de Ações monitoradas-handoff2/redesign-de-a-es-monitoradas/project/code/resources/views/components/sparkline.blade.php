{{-- Sparkline intradia em SVG puro. points: array<float>; prev: fechamento anterior (linha tracejada). --}}
@props(['points' => [], 'min' => null, 'max' => null, 'prev' => null, 'trend' => 'flat', 'w' => 240, 'h' => 52])
@php
  $pts = array_values($points);
  $n = count($pts);
  $lo = $min ?? ($n ? min($pts) : 0);
  $hi = $max ?? ($n ? max($pts) : 1);
  $span = max($hi - $lo, 1e-9);
  $pad = 4;
  $y = fn ($v) => round($pad + ($hi - min(max($v, $lo), $hi)) / $span * ($h - 2 * $pad), 1);
  $d = '';
  foreach ($pts as $i => $v) {
      $x = $n > 1 ? round($i / ($n - 1) * $w, 1) : 0;
      $d .= ($i ? 'L' : 'M') . $x . ' ' . $y($v) . ' ';
  }
  $d = trim($d);
  $endTop = $n ? round($y(end($pts)) / $h * 100, 1) : 50;
@endphp
@if ($n > 1)
<div {{ $attributes->merge(['class' => "spark spark--{$trend}"]) }} aria-hidden="true">
  <svg viewBox="0 0 {{ $w }} {{ $h }}" preserveAspectRatio="none">
    <path class="spark__area" d="{{ $d }} L{{ $w }} {{ $h }} L0 {{ $h }} Z"/>
    @if (!is_null($prev))
      <line class="spark__prev" x1="0" x2="{{ $w }}" y1="{{ $y($prev) }}" y2="{{ $y($prev) }}"/>
    @endif
    <path class="spark__line" d="{{ $d }}"/>
  </svg>
  <span class="spark__dot" style="top: {{ $endTop }}%"></span>
</div>
@endif
