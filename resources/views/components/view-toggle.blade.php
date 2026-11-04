{{-- Alternador Grade / Lista (só ≥1024px). A escolha fica em localStorage e é aplicada
     no <html data-layout> pelo layout antes do paint; o sentinel.js liga os botões. --}}
<div class="seg seg--icon view-toggle" role="group" aria-label="Visualização" data-layout-toggle>
  <button type="button" aria-label="Grade" aria-pressed="true" data-layout-set="grid">
    <svg width="14" height="14" viewBox="0 0 14 14" fill="currentColor" aria-hidden="true"><rect x="1" y="1" width="5" height="5" rx="1.2"/><rect x="8" y="1" width="5" height="5" rx="1.2"/><rect x="1" y="8" width="5" height="5" rx="1.2"/><rect x="8" y="8" width="5" height="5" rx="1.2"/></svg>
  </button>
  <button type="button" aria-label="Lista" aria-pressed="false" data-layout-set="list">
    <svg width="14" height="14" viewBox="0 0 14 14" fill="currentColor" aria-hidden="true"><rect x="1" y="2" width="12" height="1.6" rx=".8"/><rect x="1" y="6.2" width="12" height="1.6" rx=".8"/><rect x="1" y="10.4" width="12" height="1.6" rx=".8"/></svg>
  </button>
</div>
