{{--
  Gráfico de evolução do patrimônio: valor de mercado (linha + área) × custo da posição
  (tracejada), pregão a pregão. Um eixo só (R$); a distância entre as linhas é o resultado.
  Desenho em SVG com preserveAspectRatio="none" + traço que não escala; eixos e rótulos em
  HTML posicionado em %, para o texto não deformar. Hover/crosshair: resources/js/pages/evolucao.js
  lê os pontos de data-points.

  pontos  CarteiraService::evolucao()['pontos']: ['data','base','valor','custo','aportes','resgates','rentabilidade']
--}}
@props(['pontos' => []])
@php
  $n = count($pontos);
  $W = 1000; $H = 260; $pad = 10;

  $vals = array_merge(array_column($pontos, 'valor'), array_column($pontos, 'custo'));
  $lo = $n ? min($vals) : 0; $hi = $n ? max($vals) : 1;
  if ($hi - $lo < 1e-6) { $lo -= 1; $hi += 1; }

  // Escala "redonda": passo 1/2/2,5/5 × 10^k para ~4 divisões
  $bruto = ($hi - $lo) / 4;
  $mag = 10 ** floor(log10($bruto));
  $norm = $bruto / $mag;
  $passo = ($norm <= 1 ? 1 : ($norm <= 2 ? 2 : ($norm <= 2.5 ? 2.5 : ($norm <= 5 ? 5 : 10)))) * $mag;
  $yMin = floor($lo / $passo) * $passo;
  $yMax = ceil($hi / $passo) * $passo;
  $ticks = [];
  for ($v = $yMin; $v <= $yMax + $passo / 2; $v += $passo) { $ticks[] = $v; }

  $x = fn ($i) => $n > 1 ? $i / ($n - 1) * $W : $W / 2;
  $y = fn ($v) => $pad + ($yMax - $v) / max($yMax - $yMin, 1e-9) * ($H - 2 * $pad);
  $linha = function ($campo) use ($pontos, $x, $y) {
      $d = '';
      foreach ($pontos as $i => $p) { $d .= ($i ? 'L' : 'M') . round($x($i), 1) . ' ' . round($y($p[$campo]), 1) . ' '; }
      return trim($d);
  };
  $dValor = $linha('valor');
  $dCusto = $linha('custo');

  // Rótulo do eixo Y: completo abaixo de 10 mil, compacto acima
  $rotuloY = function ($v) use ($passo) {
      if (abs($v) < 10000) { return number_format($v, $passo < 1 ? 2 : 0, ',', '.'); }
      $casas = fmod($passo, 1000) == 0 ? 0 : 1;
      return number_format($v / 1000, $casas, ',', '.') . ' mil';
  };

  // Até 6 datas no eixo X, distribuídas
  $qtdX = min($n, 6);
  $idxX = $qtdX > 1 ? array_unique(array_map(fn ($k) => (int) round($k * ($n - 1) / ($qtdX - 1)), range(0, $qtdX - 1))) : [0];
  $dm = fn ($d) => \Illuminate\Support\Carbon::parse($d)->format('d/m');

  $movs = array_keys(array_filter($pontos, fn ($p) => $p['aportes'] > 0 || $p['resgates'] > 0));
  $ultimo = $n ? $pontos[$n - 1] : null;
@endphp
<div class="evo-chart" data-evo-chart data-points='@json($pontos)'>
  <div class="evo-chart__plot">
    <div class="evo-chart__yaxis" aria-hidden="true">
      @foreach ($ticks as $t)
        <span style="top: {{ round($y($t) / $H * 100, 2) }}%">{{ $rotuloY($t) }}</span>
      @endforeach
    </div>
    <div class="evo-chart__canvas">
      @foreach ($ticks as $t)
        <span class="evo-chart__grid" style="top: {{ round($y($t) / $H * 100, 2) }}%" aria-hidden="true"></span>
      @endforeach
      <svg viewBox="0 0 {{ $W }} {{ $H }}" preserveAspectRatio="none" role="img"
           aria-label="Evolução do patrimônio: de R$ {{ number_format($pontos[0]['valor'] ?? 0, 2, ',', '.') }} a R$ {{ number_format($ultimo['valor'] ?? 0, 2, ',', '.') }} em {{ $n }} pregão(ões); tabela com os valores abaixo">
        <path class="evo-chart__area" d="{{ $dValor }} L{{ round($x($n - 1), 1) }} {{ $H }} L0 {{ $H }} Z"/>
        <path class="evo-chart__custo" d="{{ $dCusto }}"/>
        <path class="evo-chart__valor" d="{{ $dValor }}"/>
      </svg>
      {{-- Dias com compra/venda: marcador sobre a linha de valor --}}
      @foreach ($movs as $i)
        <span class="evo-chart__mov" style="left: {{ round($x($i) / $W * 100, 2) }}%; top: {{ round($y($pontos[$i]['valor']) / $H * 100, 2) }}%" aria-hidden="true"></span>
      @endforeach
      @if ($ultimo)
        <span class="evo-chart__end" style="left: {{ round($x($n - 1) / $W * 100, 2) }}%; top: {{ round($y($ultimo['valor']) / $H * 100, 2) }}%" aria-hidden="true"></span>
      @endif
      <span class="evo-chart__cross" hidden data-evo-cross aria-hidden="true"></span>
      <span class="evo-chart__hover" hidden data-evo-dot aria-hidden="true"></span>
      <div class="evo-chart__tip" role="status" hidden data-evo-tip></div>
      <div class="evo-chart__hit" tabindex="0" aria-label="Explorar valores diários com as setas" data-evo-hit></div>
    </div>
  </div>
  <div class="evo-chart__xaxis" aria-hidden="true">
    @foreach ($idxX as $i)
      <span style="left: {{ round($x($i) / $W * 100, 2) }}%">{{ $dm($pontos[$i]['data']) }}</span>
    @endforeach
  </div>
</div>
