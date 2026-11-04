{{--
  Campos do formulário de movimentação (registrar e editar).
  Recebe: $ativos (id, ticker, nome, preco, cotas) e $m (valores atuais; [] ao registrar).
  O movimentacoes-form.js preenche o preço com a última cotação, mostra o total em
  tempo real e o saldo de cotas do ativo quando o tipo é venda.
--}}
@php
    $tipoAtual = old('tipo', $m['tipo'] ?? \App\Enums\TipoMovimentacao::COMPRA->value);
@endphp

<fieldset class="tipo-toggle" aria-label="Tipo de movimentação">
    @foreach (\App\Enums\TipoMovimentacao::cases() as $t)
        <label class="tipo-toggle__opt tipo-toggle__opt--{{ $t->value }}">
            <input type="radio" name="tipo" value="{{ $t->value }}" @checked($tipoAtual === $t->value)>
            <span>{{ $t->label() }}</span>
        </label>
    @endforeach
</fieldset>
@error('tipo')
    <span class="field__error">{{ $message }}</span>
@enderror

<label class="field field--ativo">
    <span class="field__label">Ativo</span>
    <select name="acao_id" id="mov-ativo" required>
        <option value="" data-preco="" data-cotas="">Selecione…</option>
        @foreach ($ativos as $a)
            <option value="{{ $a['id'] }}" data-preco="{{ $a['preco'] }}" data-cotas="{{ $a['cotas'] }}" @selected(old('acao_id', $m['acao_id'] ?? null) == $a['id'])>{{ $a['ticker'] }}{{ $a['nome'] ? ' — ' . $a['nome'] : '' }}</option>
        @endforeach
    </select>
    <span class="field__hint" id="mov-saldo" hidden></span>
    @error('acao_id')
        <span class="field__error">{{ $message }}</span>
    @enderror
</label>

<div class="mov-grid">
    <label class="field">
        <span class="field__label">Preço unitário (R$)</span>
        <input type="number" name="preco" id="mov-preco" step="0.01" min="0" value="{{ old('preco', $m['preco'] ?? null) }}" placeholder="0,00" inputmode="decimal" required>
        @error('preco')
            <span class="field__error">{{ $message }}</span>
        @enderror
    </label>

    <label class="field">
        <span class="field__label">Quantidade</span>
        <input type="number" name="quantidade" id="mov-qtd" step="1" min="1" value="{{ old('quantidade', $m['quantidade'] ?? null) }}" placeholder="0" inputmode="numeric" required>
        @error('quantidade')
            <span class="field__error">{{ $message }}</span>
        @enderror
    </label>

    <label class="field">
        <span class="field__label">Data da operação</span>
        <input type="date" name="data" id="mov-data" value="{{ old('data', $m['data'] ?? date('Y-m-d')) }}" max="{{ date('Y-m-d') }}">
        @error('data')
            <span class="field__error">{{ $message }}</span>
        @enderror
    </label>
</div>

<div class="mov-total">
    <span class="field__label" id="mov-total-label">Total</span>
    <strong id="mov-total-valor">R$ 0,00</strong>
</div>
