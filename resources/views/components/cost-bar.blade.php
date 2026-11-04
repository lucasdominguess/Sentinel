{{-- Barra valor atual × custo. Trilho = max(custo, valor); tick marca o custo. --}}
@props(['cost', 'value', 'left' => null, 'right' => null, 'size' => null])
@php
  $trend = $value - $cost > 0.004 ? 'up' : ($value - $cost < -0.004 ? 'down' : 'flat');
  $mx = max($cost, $value, 1e-9);
@endphp
<div {{ $attributes->merge(['class' => "cost-bar cost-bar--{$trend}" . ($size ? " cost-bar--{$size}" : '')]) }}>
  <div class="cost-bar__track" role="img" aria-label="Valor atual equivale a {{ number_format($value / max($cost, 1e-9) * 100, 1, ',', '.') }}% do custo">
    <span class="cost-bar__fill" style="width: {{ round($value / $mx * 100, 1) }}%"></span>
    <span class="cost-bar__cost" style="left: {{ round($cost / $mx * 100, 1) }}%" title="Total investido"></span>
  </div>
  @if ($left || $right)
    <div class="cost-bar__labels"><span>{{ $left }}</span><span>{{ $right }}</span></div>
  @endif
</div>
