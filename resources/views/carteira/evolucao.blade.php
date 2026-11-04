{{--
  Evolução do patrimônio entre duas datas (atalho de período ou intervalo personalizado).
  Recebe do PainelController@evolucao:
    $evolucao    CarteiraService::evolucao(): ['de','ate','base','pontos','resumo','ativos']
    $periodo     ?string atalho ativo (null = personalizado); $periodos [chave => rótulo]
    $pedido      ['de','ate'] pedidos (antes de ajustar à primeira movimentação)
    $calendario  ['min','max','pregoes'] para o <x-date-picker>
--}}
@extends('layouts.monitor')

@section('title', 'Evolução do patrimônio · Sentinel')

@push('styles')
    @vite('resources/css/pages/evolucao.css')
@endpush

@section('content')
    @php
        $brl = fn ($v) => number_format((float) $v, 2, ',', '.');
        $dataBr = fn ($d) => \Illuminate\Support\Carbon::parse($d)->format('d/m/Y');
        $tom = fn ($v) => $v > 0.004 ? 'up' : ($v < -0.004 ? 'down' : 'flat');
        $sinal = fn ($v) => ['up' => '+', 'down' => '−', 'flat' => ''][$tom($v)];
        $rs = fn ($v) => $sinal($v) . 'R$ ' . $brl(abs($v));

        $r = $evolucao['resumo'];
        $pontos = $evolucao['pontos'];
        $ativos = collect($evolucao['ativos']);
        $maxGanho = max($ativos->max(fn ($a) => abs($a['ganho'])) ?? 0, 1e-9);
        $ajustado = $evolucao['de'] !== $pedido['de'];
    @endphp

    <section class="page-header">
        <div class="page-header__title">
            <span class="kicker">Carteira · Evolução</span>
            <h1 class="h1">Evolução do patrimônio</h1>
            <p class="page-header__lead">Quanto a carteira rendeu no período, separando o dinheiro que você aplicou do que o mercado fez.</p>
        </div>
        <div class="page-header__actions">
            <a class="btn btn-ghost" href="{{ route('painel.carteira') }}">Meus ativos</a>
        </div>
    </section>

    @if (!$calendario['min'])
        <div class="empty" style="margin-top:28px">
            <div class="empty__icon" aria-hidden="true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 17l6-6 4 4 8-8"/><path d="M14 7h7v7"/></svg>
            </div>
            <h2>Nenhuma movimentação registrada</h2>
            <p>A evolução começa na sua primeira compra. Registre uma movimentação para acompanhar o patrimônio dia a dia.</p>
            <a class="btn btn-primary" href="{{ route('painel.movimentacoes.create') }}">Registrar movimentação</a>
        </div>
    @else
        <div class="toolbar">
            <nav class="seg" aria-label="Período">
                @foreach ($periodos as $k => $rotulo)
                    <a href="{{ route('painel.carteira.evolucao', ['periodo' => $k]) }}" @if($periodo === $k) aria-current="true" @endif>{{ $rotulo }}</a>
                @endforeach
            </nav>
            <div class="evo-range">
                <span class="label">Personalizado</span>
                <x-date-picker class="datepicker--end" mode="range" :action="route('painel.carteira.evolucao')"
                               :value="['de' => $evolucao['de'], 'ate' => $evolucao['ate']]"
                               :min="$calendario['min']" :max="$calendario['max']" :pregoes="$calendario['pregoes']" label="Período" />
            </div>
        </div>

        <p class="results-meta">
            {{ $dataBr($evolucao['de']) }} – {{ $dataBr($evolucao['ate']) }}
            @if ($r) · {{ $r['pregoes'] }} {{ $r['pregoes'] === 1 ? 'pregão' : 'pregões' }} @endif
            @if ($evolucao['base']) · ponto de partida: fechamento de {{ $dataBr($evolucao['base']) }} @endif
            @if ($ajustado) · início ajustado à primeira movimentação @endif
        </p>

        @if (!$r)
            <div class="empty">
                <div class="empty__icon" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="3" width="12" height="11" rx="2"/><path d="M2 6.5h12M5.5 1.5v3M10.5 1.5v3" stroke-linecap="round"/></svg>
                </div>
                <h2>Sem pregões no período</h2>
                <p>Não há fechamentos registrados entre essas datas. Escolha outro período.</p>
            </div>
        @else
            <dl class="evo-kpis">
                <div class="card evo-kpi">
                    <dt>Patrimônio</dt>
                    <dd class="evo-kpi__val"><span class="price__cur">R$</span>{{ $brl($r['valor_final']) }}</dd>
                    <dd class="evo-kpi__sub">{{ $evolucao['base'] ? 'era R$ ' . $brl($r['valor_inicial']) . ' em ' . $dataBr($evolucao['base']) : 'carteira iniciada no período' }}</dd>
                </div>
                <div class="card evo-kpi">
                    <dt>Aportes líquidos</dt>
                    <dd class="evo-kpi__val">{{ $rs($r['fluxo']) }}</dd>
                    <dd class="evo-kpi__sub">compras R$ {{ $brl($r['aportes']) }} · vendas R$ {{ $brl($r['resgates']) }}</dd>
                </div>
                <div class="card evo-kpi">
                    <dt>Ganho de mercado</dt>
                    <dd class="evo-kpi__val tone-{{ $tom($r['ganho']) }}">{{ $rs($r['ganho']) }}</dd>
                    <dd class="evo-kpi__sub">
                        @if ($r['rentabilidade'] !== null)
                            <x-badge-var :pct="$r['rentabilidade']" /> rentabilidade no período
                        @else
                            sem pregão anterior para comparar
                        @endif
                    </dd>
                </div>
                <div class="card evo-kpi">
                    <dt>Lucro realizado</dt>
                    <dd class="evo-kpi__val tone-{{ $tom($r['realizado']) }}">{{ $rs($r['realizado']) }}</dd>
                    <dd class="evo-kpi__sub">das vendas no período</dd>
                </div>
            </dl>

            <section class="card evo-card" aria-labelledby="evo-chart-title">
                <div class="evo-card__head">
                    <h2 class="evo-card__title" id="evo-chart-title">Patrimônio dia a dia</h2>
                    <ul class="evo-legend" aria-label="Legenda">
                        <li><span class="evo-legend__key evo-legend__key--valor" aria-hidden="true"></span>Valor de mercado</li>
                        <li><span class="evo-legend__key evo-legend__key--custo" aria-hidden="true"></span>Custo da posição</li>
                        <li><span class="evo-legend__key evo-legend__key--mov" aria-hidden="true"></span>Dia com compra/venda</li>
                    </ul>
                </div>
                @if (count($pontos) > 1)
                    <x-evolucao-chart :pontos="$pontos" />
                @else
                    <p class="evo-card__note">Só há um pregão no período — escolha um intervalo maior para ver a curva.</p>
                @endif

                <details class="evo-table">
                    <summary>Ver valores por pregão</summary>
                    <div class="evo-table__wrap">
                        <table>
                            <thead>
                                <tr><th>Pregão</th><th class="r">Valor de mercado</th><th class="r">Custo</th><th class="r">Resultado</th><th class="r">Compras</th><th class="r">Vendas</th><th class="r">Rentab. acum.</th></tr>
                            </thead>
                            <tbody>
                                @foreach (array_reverse($pontos) as $p)
                                    <tr>
                                        <td>{{ $dataBr($p['data']) }}@if ($p['base']) <span class="tag">partida</span>@endif</td>
                                        <td class="r">R$ {{ $brl($p['valor']) }}</td>
                                        <td class="r">R$ {{ $brl($p['custo']) }}</td>
                                        <td class="r tone-{{ $tom($p['valor'] - $p['custo']) }}">{{ $rs($p['valor'] - $p['custo']) }}</td>
                                        <td class="r">{{ $p['aportes'] > 0 ? 'R$ ' . $brl($p['aportes']) : '—' }}</td>
                                        <td class="r">{{ $p['resgates'] > 0 ? 'R$ ' . $brl($p['resgates']) : '—' }}</td>
                                        <td class="r">{{ $p['base'] ? '—' : number_format($p['rentabilidade'], 2, ',', '.') . '%' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </details>
            </section>

            <section class="card evo-card" aria-labelledby="evo-ativos-title">
                <div class="evo-card__head">
                    <h2 class="evo-card__title" id="evo-ativos-title">Contribuição por ativo</h2>
                    <span class="evo-card__hint">ganho = valor final − valor inicial − (compras − vendas)</span>
                </div>
                <div class="evo-contrib" role="table" aria-label="Contribuição por ativo">
                    <div class="evo-contrib__row evo-contrib__row--head" role="row">
                        <span role="columnheader">Ativo</span>
                        <span role="columnheader" class="r">Início</span>
                        <span role="columnheader" class="r">Compras − vendas</span>
                        <span role="columnheader" class="r">Fim</span>
                        <span role="columnheader">Ganho no período</span>
                    </div>
                    @foreach ($ativos as $a)
                        @php $t = $tom($a['ganho']); $w = round(abs($a['ganho']) / $maxGanho * 50, 2); @endphp
                        <div class="evo-contrib__row" role="row">
                            <div role="cell" class="evo-contrib__id">
                                <a class="ticker" href="{{ route('painel.show', $a['acao_id']) }}"><x-ticker :value="$a['ticker']" /></a>
                                <span class="evo-contrib__name" title="{{ $a['nome'] }}">{{ $a['nome'] }}</span>
                            </div>
                            <span role="cell" class="r muted">{{ $a['valor_inicial'] > 0 ? 'R$ ' . $brl($a['valor_inicial']) : '—' }}</span>
                            <span role="cell" class="r muted">{{ $a['aportes'] > 0 || $a['resgates'] > 0 ? $rs($a['aportes'] - $a['resgates']) : '—' }}</span>
                            <span role="cell" class="r muted">{{ $a['valor_final'] > 0 ? 'R$ ' . $brl($a['valor_final']) : 'vendido' }}</span>
                            <div role="cell" class="evo-contrib__gain">
                                <div class="evo-bar evo-bar--{{ $t }}" aria-hidden="true">
                                    <span style="{{ $a['ganho'] >= 0 ? 'left: 50%' : 'right: 50%' }}; width: {{ $w }}%"></span>
                                </div>
                                <span class="evo-contrib__val tone-{{ $t }}">{{ $rs($a['ganho']) }}</span>
                                <span class="evo-contrib__pct">{{ $a['ganho_pct'] !== null ? number_format($a['ganho_pct'], 2, ',', '.') . '%' : '' }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    @endif
@endsection

@push('scripts')
    @vite(['resources/js/components/date-picker.js', 'resources/js/pages/evolucao.js'])
@endpush
