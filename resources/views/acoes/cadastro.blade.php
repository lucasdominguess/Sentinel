@extends('layouts.app')

@section('title', 'Cadastro de ativos — Sentinel')

@push('styles')
    @vite('resources/css/pages/acoes-cadastro.css')
@endpush

@section('content')
    @php
        $tipoLabel = fn($t) => \App\Enums\TipoAtivo::tryFrom($t)?->label() ?? $t;
    @endphp

    <div class="page-head">
        <div>
            <h1>Cadastro de ativos</h1>
            <p class="muted">{{ count($ativos) }} ativo(s) cadastrado(s). Gerencie os ativos monitorados.</p>
        </div>
        <a href="{{ route('painel.adicionar') }}" class="btn-primary">+ Adicionar ticket</a>
    </div>

    @if (session('sucesso'))
        <div class="alert alert--ok">{{ session('sucesso') }}</div>
    @endif

    @if (count($ativos) === 0)
        <div class="card empty">
            <p>Nenhum ativo cadastrado ainda.</p>
            <p class="muted">Use <a href="{{ route('painel.adicionar') }}">Adicionar ticket</a> para cadastrar o primeiro.</p>
        </div>
    @else
        <div class="toolbar">
            <div class="search-wrap">
                <svg class="search-ico" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input id="busca-ativo" class="input search" type="search" placeholder="Filtrar por ticker, nome ou setor…" autocomplete="off">
            </div>
        </div>

        <div class="card table-wrap">
            <table class="reg-table" id="tabela-ativos">
                <thead>
                    <tr>
                        <th>Ticker</th>
                        <th>Nome</th>
                        <th>Setor</th>
                        <th>Tipo</th>
                        <th>Status</th>
                        <th class="acoes-col">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($ativos as $ativo)
                        <tr data-search="{{ mb_strtolower($ativo['ticker'] . ' ' . ($ativo['nome'] ?? '') . ' ' . ($ativo['setor'] ?? '')) }}">
                            <td><span class="reg-ticker">{{ $ativo['ticker'] }}</span></td>
                            <td>{{ $ativo['nome'] ?? '—' }}</td>
                            <td>{{ $ativo['setor'] ?? '—' }}</td>
                            <td><span class="tag">{{ $tipoLabel($ativo['tipo']) }}</span></td>
                            <td>
                                @if ($ativo['ativo'])
                                    <span class="badge badge--up">Ativo</span>
                                @else
                                    <span class="badge badge--off">Inativo</span>
                                @endif
                            </td>
                            <td class="acoes-col">
                                <div class="reg-acoes">
                                    <a href="{{ route('painel.ativos.show', $ativo['id']) }}" class="btn-acao">Detalhes</a>
                                    <a href="{{ route('painel.ativos.edit', $ativo['id']) }}" class="btn-acao">Editar</a>
                                    <form method="POST" action="{{ route('painel.ativos.destroy', $ativo['id']) }}"
                                          onsubmit="return confirm('Excluir {{ $ativo['ticker'] }}? Isso remove também TODAS as cotações e aportes deste ativo. Esta ação não pode ser desfeita.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-acao btn-acao--danger">Excluir</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="no-results muted" id="sem-resultados" hidden>Nenhum ativo corresponde à busca.</p>
    @endif
@endsection

@push('scripts')
    @vite('resources/js/pages/acoes-cadastro.js')
@endpush
