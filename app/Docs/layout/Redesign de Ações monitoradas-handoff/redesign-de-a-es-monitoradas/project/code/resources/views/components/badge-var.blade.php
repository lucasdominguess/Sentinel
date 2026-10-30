@props(['pct'])
@php
  $trend = $pct > 0 ? 'up' : ($pct < 0 ? 'down' : 'flat');
  $arrow = ['up' => '▲', 'down' => '▼', 'flat' => '•'][$trend];
  $sr = ['up' => 'alta de', 'down' => 'queda de', 'flat' => 'estável'][$trend];
@endphp
<span {{ $attributes->merge(['class' => "badge {$trend}"]) }}>
  <span class="badge__arrow" aria-hidden="true">{{ $arrow }}</span>
  <span class="sr-only">{{ $sr }}</span>{{ number_format(abs($pct), 2, ',', '.') }}%
</span>
