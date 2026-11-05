{{-- Badge de variação %: cor + seta (▲/▼) + texto para leitor de tela. --}}
@props(['pct'])
@php
  // Tendência pelo valor exibido (2 casas): nada de "▼ 0,00%"
  $exibido = round((float) $pct, 2);
  $trend = $exibido > 0 ? 'up' : ($exibido < 0 ? 'down' : 'flat');
  $arrow = ['up' => '▲', 'down' => '▼', 'flat' => '•'][$trend];
  $sr = ['up' => 'alta de', 'down' => 'queda de', 'flat' => 'estável'][$trend];
@endphp
<span {{ $attributes->merge(['class' => "badge {$trend}"]) }}>
  <span class="badge__arrow" aria-hidden="true">{{ $arrow }}</span>
  <span class="sr-only">{{ $sr }}</span>{{ number_format(abs($pct), 2, ',', '.') }}%
</span>
