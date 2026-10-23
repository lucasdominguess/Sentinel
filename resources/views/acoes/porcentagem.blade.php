@extends('layouts.app')

@section('title', 'Cálculo de porcentagem — Sentinel')

@push('styles')
    @vite('resources/css/pages/acoes-porcentagem.css')
@endpush

@section('content')
    <div class="page-head">
        <div>
            <h1>Cálculo de porcentagem</h1>
            <p class="muted">Ferramentas rápidas de porcentagem.</p>
        </div>
    </div>

    <div class="card pct-card">
        {{-- 1. Quanto é A% de B --}}
        <div class="pct-tool" data-tool="quanto">
            <div class="pct-expr">
                <span>Quanto é</span>
                <input type="number" class="pct-input" placeholder="--"><span class="pct-pct">%</span>
                <span>de</span>
                <input type="number" class="pct-input" placeholder="--">
                <span>?</span>
            </div>
            <div class="pct-action">
                <button type="button" class="btn-primary pct-calc">Calcular</button>
                <span class="pct-result">----</span>
            </div>
        </div>

        <div class="pct-divider"></div>

        {{-- 2. A é qual porcentagem de B --}}
        <div class="pct-tool" data-tool="qualPct">
            <div class="pct-expr">
                <span>O valor</span>
                <input type="number" class="pct-input" placeholder="--">
                <span>é qual porcentagem de</span>
                <input type="number" class="pct-input" placeholder="--">
                <span>?</span>
            </div>
            <div class="pct-action">
                <button type="button" class="btn-primary pct-calc">Calcular</button>
                <span class="pct-result">----</span>
            </div>
        </div>

        <div class="pct-divider"></div>

        {{-- 3. Aumento percentual de A para B --}}
        <div class="pct-tool" data-tool="aumento">
            <div class="pct-expr">
                <span>Um valor de</span>
                <input type="number" class="pct-input" placeholder="--">
                <span>que <strong>AUMENTOU</strong> para</span>
                <input type="number" class="pct-input" placeholder="--">
                <span>. Qual foi o aumento percentual?</span>
            </div>
            <div class="pct-action">
                <button type="button" class="btn-primary pct-calc">Calcular</button>
                <span class="pct-result">----</span>
            </div>
        </div>

        <div class="pct-divider"></div>

        {{-- 4. Diminuição percentual de A para B --}}
        <div class="pct-tool" data-tool="reducao">
            <div class="pct-expr">
                <span>Um valor de</span>
                <input type="number" class="pct-input" placeholder="--">
                <span>que <strong>DIMINUIU</strong> para</span>
                <input type="number" class="pct-input" placeholder="--">
                <span>. Qual foi a diminuição percentual?</span>
            </div>
            <div class="pct-action">
                <button type="button" class="btn-primary pct-calc">Calcular</button>
                <span class="pct-result">----</span>
            </div>
        </div>

        <div class="pct-divider"></div>

        {{-- 5. A sobre B é quantos por cento --}}
        <div class="pct-tool" data-tool="sobre">
            <div class="pct-expr">
                <span>O valor</span>
                <input type="number" class="pct-input" placeholder="--">
                <span>sobre o valor</span>
                <input type="number" class="pct-input" placeholder="--">
                <span>é quantos por cento?</span>
            </div>
            <div class="pct-action">
                <button type="button" class="btn-primary pct-calc">Calcular</button>
                <span class="pct-result">----</span>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/pages/acoes-porcentagem.js')
@endpush
