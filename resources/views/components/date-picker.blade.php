{{--
  Calendário selecionável (form GET). Sem JS, são <input type="date"> nativos + botão "Ver";
  com JS (resources/js/components/date-picker.js) vira um botão que abre um calendário
  com os dias de pregão marcados, datas fora de [min, max] desabilitadas e, no modo
  intervalo, seleção de início e fim.

  mode   'single' (campo `data`) | 'range' (campos `de` e `ate`)
  value  'Y-m-d' (single) | ['de' => 'Y-m-d', 'ate' => 'Y-m-d'] (range)
  min/max  limites selecionáveis ('Y-m-d'); pregoes  lista de 'Y-m-d' com fechamento
  action URL do GET; label  nome acessível do controle
--}}
@props(['mode' => 'single', 'value' => null, 'min' => null, 'max' => null, 'pregoes' => [], 'action', 'label' => 'Escolher data'])
@php
  $range = $mode === 'range';
  $de = $range ? ($value['de'] ?? null) : $value;
  $ate = $range ? ($value['ate'] ?? null) : null;
  $fmt = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('d/m/Y') : '';
  $texto = $range ? trim($fmt($de) . ' – ' . $fmt($ate), ' –') : $fmt($de);
@endphp
<form {{ $attributes->merge(['class' => 'datepicker']) }} method="GET" action="{{ $action }}"
      data-datepicker data-mode="{{ $range ? 'range' : 'single' }}"
      data-min="{{ $min }}" data-max="{{ $max }}" data-pregoes='@json(array_values($pregoes))'>
  <div class="datepicker__fallback">
    @if ($range)
      <input class="input" type="date" name="de" value="{{ $de }}" min="{{ $min }}" max="{{ $max }}" aria-label="Data inicial" required>
      <span class="datepicker__sep">até</span>
      <input class="input" type="date" name="ate" value="{{ $ate }}" min="{{ $min }}" max="{{ $max }}" aria-label="Data final" required>
    @else
      <input class="input" type="date" name="data" value="{{ $de }}" min="{{ $min }}" max="{{ $max }}" aria-label="{{ $label }}" required>
    @endif
    <button class="btn btn-ghost btn--sm" type="submit">Ver</button>
  </div>

  <button type="button" class="datepicker__trigger" aria-haspopup="dialog" aria-expanded="false" aria-label="{{ $label }}: {{ $texto ?: 'nenhuma' }}" hidden data-datepicker-trigger>
    <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="2" y="3" width="12" height="11" rx="2"/><path d="M2 6.5h12M5.5 1.5v3M10.5 1.5v3" stroke-linecap="round"/></svg>
    <span data-datepicker-text>{{ $texto ?: 'Escolher' }}</span>
    <svg class="datepicker__chev" width="10" height="10" viewBox="0 0 10 10" aria-hidden="true"><path d="M2 3.75L5 6.75l3-3" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
  </button>
  <div class="datepicker__pop" role="dialog" aria-label="{{ $label }}" hidden data-datepicker-pop></div>
</form>
