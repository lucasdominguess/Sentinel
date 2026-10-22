@extends('layouts.app')

@section('title', 'Cálculo de cotas — Sentinel')

@push('styles')
    @vite('resources/css/pages/acoes-calculo.css')
@endpush

@section('content')
    <div class="page-head">
        <div>
            <h1>Cálculo de quantidade de cotas</h1>
            <p class="muted">Distribua um valor de aporte entre os ativos e veja quantas cotas comprar de cada.</p>
        </div>
    </div>

    <div class="card calc-card">
        <label class="calc-aporte">
            <span class="calc-label">Valor total do aporte (R$)</span>
            <input id="calc-valor" type="number" step="0.01" placeholder="Ex: 3000.00" inputmode="decimal">
        </label>

        <h2 class="calc-sub">Distribuição dos ativos</h2>
        <p class="muted small">Selecione o ativo: o preço vem da última cotação (editável, para simular um preço-alvo) e o dividendo/cota é o <strong>último provento pago</strong> por cota (ativos sem provento ficam em branco). A soma das % Alvo deve dar 100%.</p>

        <div id="calc-rows"></div>

        <button type="button" id="calc-add" class="btn-ghost">+ Adicionar ativo</button>

        <p id="calc-erro" class="calc-erro" hidden></p>

        <div class="calc-actions">
            <button type="button" id="calc-run" class="btn-primary">Calcular distribuição</button>
        </div>
    </div>

    <div id="calc-resultado" class="card calc-resultado" hidden>
        <h2 class="calc-sub">Resultado</h2>
        <div class="calc-table-wrap">
            <table class="calc-table">
                <thead>
                    <tr>
                        <th>Ativo</th>
                        <th>% Alvo</th>
                        <th>Cotas</th>
                        <th>Investido</th>
                        <th>Troco</th>
                        <th>Dividendos</th>
                    </tr>
                </thead>
                <tbody id="calc-tbody"></tbody>
            </table>
        </div>
        <p class="calc-troco">Troco total acumulado: <strong id="calc-troco-total">R$ 0,00</strong></p>
    </div>

    {{-- Modelo de linha clonado pelo JS; o select já vem com os ativos do sistema --}}
    <template id="calc-row-template">
        <div class="calc-row">
            <label class="calc-field calc-field--ativo">
                <span class="calc-label">Ativo</span>
                <select class="calc-ativo">
                    <option value="" data-preco="">Selecione…</option>
                    @foreach ($ativos as $a)
                        <option value="{{ $a['ticker'] }}" data-preco="{{ $a['preco'] }}" data-dividendo="{{ $a['dividendo'] }}">{{ $a['ticker'] }}{{ $a['nome'] ? ' — ' . $a['nome'] : '' }}</option>
                    @endforeach
                </select>
            </label>
            <label class="calc-field">
                <span class="calc-label">Preço (R$)</span>
                <input class="calc-preco" type="number" step="0.01" placeholder="0.00" inputmode="decimal">
            </label>
            <label class="calc-field calc-field--sm">
                <span class="calc-label">% Alvo</span>
                <input class="calc-porc" type="number" step="1" placeholder="0" inputmode="numeric">
            </label>
            <label class="calc-field">
                <span class="calc-label">Dividendo/cota (R$)</span>
                <input class="calc-dividendo" type="number" step="0.01" placeholder="0.00" inputmode="decimal">
            </label>
            <label class="calc-field calc-field--sm">
                <span class="calc-label">% Paga</span>
                <input class="calc-paga" type="number" placeholder="—" disabled>
            </label>
            <button type="button" class="calc-remove btn-icon" title="Remover ativo" aria-label="Remover ativo">✕</button>
        </div>
    </template>
@endsection

@push('scripts')
    @vite('resources/js/pages/acoes-calculo.js')
@endpush
