{{--
  Donut de alocação em SVG puro (r = 15.9155 → circunferência 100, então dasharray = %).
  $segments: array de ['label' => 'VGIR11', 'value' => 10540.35, 'color' => 'var(--cat-1)']
  Hover/foco na legenda destaca a fatia via :has() (components.css) + JS para o centro.
--}}
@props(['segments' => [], 'centerValue' => '', 'centerLabel' => '', 'label' => 'Alocação'])
@php
  $total = max(array_sum(array_column($segments, 'value')), 1e-9);
  $brl = fn ($v) => number_format($v, 2, ',', '.');
  $acc = 0; $gap = 0.8; $rows = [];
  foreach ($segments as $i => $s) {
      $p = $s['value'] / $total * 100;
      $len = max($p - $gap, 0.3);
      $rows[] = $s + ['pct' => $p, 'dash' => round($len, 2) . ' ' . round(100 - $len, 2), 'offset' => round(-$acc, 2), 'key' => 'seg-' . $i];
      $acc += $p;
  }
@endphp
<div class="donut-wrap" data-donut>
  <figure class="donut" role="img" aria-label="{{ $label }}: {{ collect($rows)->map(fn ($r) => $r['label'] . ' ' . number_format($r['pct'], 1, ',', '.') . '%')->join(', ') }}" style="margin:0">
    <svg viewBox="0 0 42 42" aria-hidden="true">
      <circle class="donut__track" cx="21" cy="21" r="15.9155"/>
      @foreach ($rows as $r)
        <circle class="donut__seg" data-seg="{{ $r['key'] }}" cx="21" cy="21" r="15.9155"
                stroke="{{ $r['color'] }}" stroke-dasharray="{{ $r['dash'] }}" stroke-dashoffset="{{ $r['offset'] }}"/>
      @endforeach
    </svg>
    <figcaption class="donut__center">
      <span class="donut__val" data-donut-val data-default="{{ $centerValue }}">{{ $centerValue }}</span>
      <span class="donut__label" data-donut-label data-default="{{ $centerLabel }}">{{ $centerLabel }}</span>
    </figcaption>
  </figure>
  <ul class="legend">
    @foreach ($rows as $r)
      <li class="legend__item" tabindex="0" data-seg="{{ $r['key'] }}" data-pct="{{ number_format($r['pct'], 1, ',', '.') }}%" data-name="{{ $r['label'] }}" style="--c: {{ $r['color'] }}">
        <span class="legend__swatch" aria-hidden="true"></span>
        <span class="legend__name">{{ $r['label'] }}</span>
        <span class="legend__val">R$ {{ $brl($r['value']) }}</span>
        <span class="legend__pct">{{ number_format($r['pct'], 1, ',', '.') }}%</span>
      </li>
    @endforeach
  </ul>
</div>
@once
<script>
  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-donut]').forEach(w => {
      const val = w.querySelector('[data-donut-val]'), lab = w.querySelector('[data-donut-label]');
      const set = li => {
        w.querySelectorAll('.donut__seg').forEach(s => s.classList.toggle('is-active', !!li && s.dataset.seg === li.dataset.seg));
        val.textContent = li ? li.dataset.pct : val.dataset.default;
        lab.textContent = li ? li.dataset.name : lab.dataset.default;
      };
      w.querySelectorAll('.legend__item').forEach(li => {
        ['mouseenter', 'focus'].forEach(e => li.addEventListener(e, () => set(li)));
        ['mouseleave', 'blur'].forEach(e => li.addEventListener(e, () => set(null)));
      });
    });
  });
</script>
@endonce
