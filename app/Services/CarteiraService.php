<?php

namespace App\Services;

use App\Contracts\Repositories\CotacaoDiariaRepositoryInterface;
use App\Contracts\Repositories\MovimentacaoRepositoryInterface;
use App\Contracts\Services\CarteiraServiceInterface;
use App\Contracts\Services\MovimentacaoServiceInterface;
use App\Enums\TipoMovimentacao;
use App\Services\Carteira\LivroCarteira;
use Illuminate\Support\Carbon;

/**
 * Carteira no tempo: a foto da posição em uma data e a evolução do patrimônio
 * entre duas datas. Tudo é derivado na hora das movimentações (cotas por data)
 * e dos fechamentos diários (preço por data) — nada é gravado, então corrigir
 * uma movimentação antiga corrige o histórico inteiro.
 */
class CarteiraService implements CarteiraServiceInterface
{
    public function __construct(
        private MovimentacaoRepositoryInterface $movimentacoes,
        private CotacaoDiariaRepositoryInterface $fechamentos,
        private MovimentacaoServiceInterface $movimentacaoService,
    ) {}

    // Limites do calendário: da primeira movimentação até hoje, com os dias de pregão
    public function calendario(): array
    {
        $min = $this->movimentacoes->primeiraData();
        $hoje = Carbon::today()->toDateString();

        return [
            'min' => $min,
            'max' => $hoje,
            'pregoes' => $min ? $this->fechamentos->pregoes($min, $hoje) : [],
        ];
    }

    // Posição no fechamento da data (ou do último pregão antes dela, em fim de semana/feriado)
    public function fotoDoDia(string $data): array
    {
        $ids = $this->movimentacoes->daCarteira($data)->pluck('acao_id')->unique()->values()->all();

        $porAtivo = $this->fechamentos->ultimosFechamentos($ids, $data)->groupBy('acao_id');
        $pregao = $porAtivo->map(fn ($rows) => $rows[0]->data->toDateString())->max();

        // Ativo sem fechamento no pregão da foto vale o último fechamento dele e não
        // variou nesse pregão (a variação entre os dois fechamentos dele é de outro dia)
        $precos = [];
        foreach ($porAtivo as $acaoId => $rows) {
            $negociou = $rows[0]->data->toDateString() === $pregao;
            $precos[$acaoId] = [
                'preco' => $rows[0]->fechamento,
                'anterior' => $negociou ? ($rows[1]->fechamento ?? null) : $rows[0]->fechamento,
                'data' => $pregao,
            ];
        }

        return [
            'pregao' => $pregao,
            'posicoes' => $this->movimentacaoService->posicoes($data, $precos),
        ];
    }

    /**
     * Evolução do patrimônio entre $de e $ate (inclusive), pregão a pregão.
     *
     * O ponto de partida é o fechamento do último pregão ANTES de $de (a carteira
     * como estava ao entrar no período). Movimentações dentro do período são
     * fluxo de caixa: compras entram como aporte, vendas como resgate. Assim:
     *   ganho de mercado = valor final − valor inicial − (aportes − resgates)
     * e a rentabilidade é ponderada no tempo (TWR): o retorno de cada dia
     * desconta o dinheiro que entrou/saiu nele, então um aporte grande não
     * aparece como rendimento. Compras contam do início do dia (o preço pago
     * até o fechamento já é resultado) e vendas do fim:
     *   retorno do dia = (valor + resgates) / (valor anterior + aportes) − 1
     */
    public function evolucao(string $de, string $ate): array
    {
        $primeira = $this->movimentacoes->primeiraData();
        $vazio = ['de' => $de, 'ate' => $ate, 'base' => null, 'pontos' => [], 'resumo' => null, 'ativos' => []];

        if (!$primeira || $ate < $primeira) {
            return $vazio;
        }

        // Antes da primeira movimentação a carteira não existe: o período começa nela
        $de = max($de, $primeira);

        $movs = LivroCarteira::cronologica($this->movimentacoes->daCarteira($ate));
        $acoes = $movs->pluck('acao', 'acao_id');
        $ids = $acoes->keys()->all();

        $anteriores = $this->fechamentos->pregoes(
            Carbon::parse($de)->subDays(20)->toDateString(),
            Carbon::parse($de)->subDay()->toDateString(),
        );
        $base = $de > $primeira ? (end($anteriores) ?: null) : null;
        $datas = array_values(array_filter([$base, ...$this->fechamentos->pregoes($de, $ate)]));

        if (empty($datas)) {
            return ['de' => $de] + $vazio;
        }

        // Preço por ativo com carry-forward: semente no último fechamento até o 1º dia,
        // depois a série do período (dia sem fechamento de um ativo repete o anterior)
        $preco = [];
        foreach ($this->fechamentos->ultimosFechamentos($ids, $datas[0], 1) as $f) {
            $preco[$f->acao_id] = (float) $f->fechamento;
        }
        $serie = $this->fechamentos->fechamentosEntre($ids, $datas[0], $ate)
            ->groupBy(fn ($f) => $f->data->toDateString());

        // Pregão em andamento: o candle do dia em cotacoes_diarias chega atrasado em
        // relação à cotação. No último ponto vale a cotação capturada nesse dia — o
        // mesmo preço de "Meus ativos", para as duas telas fecharem.
        $ultimoDia = end($datas);
        $aoVivo = $acoes
            ->filter(fn ($acao) => $acao?->ultimaCotacao?->preco !== null
                && $acao->ultimaCotacao->capturado_em?->toDateString() === $ultimoDia)
            ->map(fn ($acao) => (float) $acao->ultimaCotacao->preco)
            ->all();

        $livro = new LivroCarteira();
        $i = 0;
        $n = $movs->count();
        $pontos = [];
        $fluxoAtivo = [];
        $valorAtivo = [];
        $valorInicialAtivo = [];
        $twr = 1.0;
        $teveRetorno = false;
        $valorAnterior = null;

        foreach ($datas as $k => $dia) {
            foreach ($serie[$dia] ?? [] as $f) {
                $preco[$f->acao_id] = (float) $f->fechamento;
            }
            if ($dia === $ultimoDia) {
                $preco = $aoVivo + $preco;
            }

            // No ponto de partida as movimentações são histórico, não fluxo do período
            $contaFluxo = !($k === 0 && $base !== null);
            $aportes = $resgates = $realizado = 0.0;

            while ($i < $n && $movs[$i]->data->toDateString() <= $dia) {
                $m = $movs[$i++];
                $lucro = $livro->aplicar($m);
                if (!$contaFluxo) {
                    continue;
                }

                $f = &$fluxoAtivo[$m->acao_id];
                $f ??= ['aportes' => 0.0, 'resgates' => 0.0, 'realizado' => 0.0];
                if ($m->tipo === TipoMovimentacao::COMPRA) {
                    $aportes += (float) $m->total;
                    $f['aportes'] += (float) $m->total;
                } else {
                    $resgates += (float) $m->total;
                    $realizado += $lucro;
                    $f['resgates'] += (float) $m->total;
                    $f['realizado'] += $lucro;
                }
                unset($f);
            }

            // Valor de mercado do dia; ativo ainda sem fechamento vale o custo. Cada ativo
            // em centavos (como em "Meus ativos"), para o total fechar com a soma por ativo
            $valor = $custo = 0.0;
            $valorAtivo = [];
            foreach ($livro->posicoes() as $acaoId => $p) {
                $v = $p['quantidade'] > 0
                    ? round(isset($preco[$acaoId]) ? $p['quantidade'] * $preco[$acaoId] : $p['custo'], 2)
                    : 0.0;
                $valorAtivo[$acaoId] = $v;
                $valor += $v;
                $custo += round($p['custo'], 2);
            }

            if ($k === 0 && $base !== null) {
                $valorInicialAtivo = $valorAtivo;
            }

            // TWR: retorno do dia sem o dinheiro que entrou/saiu nele
            $investidoNoDia = ($valorAnterior ?? 0.0) + $aportes;
            if ($k > 0 || $base === null) {
                if ($investidoNoDia > 0) {
                    $twr *= ($valor + $resgates) / $investidoNoDia;
                    $teveRetorno = true;
                }
            }
            $valorAnterior = $valor;

            $pontos[] = [
                'data' => $dia,
                'base' => !$contaFluxo,
                'valor' => round($valor, 2),
                'custo' => round($custo, 2),
                'aportes' => round($aportes, 2),
                'resgates' => round($resgates, 2),
                'realizado' => round($realizado, 2),
                'rentabilidade' => $teveRetorno ? ($twr - 1) * 100 : 0.0,
            ];
        }

        $inicial = $base !== null ? $pontos[0]['valor'] : 0.0;
        $final = end($pontos);
        $periodo = array_filter($pontos, fn ($p) => !$p['base']);
        $aportes = array_sum(array_column($periodo, 'aportes'));
        $resgates = array_sum(array_column($periodo, 'resgates'));

        $resumo = [
            'valor_inicial' => $inicial,
            'valor_final' => $final['valor'],
            'custo_final' => $final['custo'],
            'aportes' => round($aportes, 2),
            'resgates' => round($resgates, 2),
            'fluxo' => round($aportes - $resgates, 2),
            'ganho' => round($final['valor'] - $inicial - ($aportes - $resgates), 2),
            'rentabilidade' => $teveRetorno ? ($twr - 1) * 100 : null,
            'realizado' => round(array_sum(array_column($periodo, 'realizado')), 2),
            'pregoes' => count($periodo),
        ];

        // Contribuição de cada ativo para o ganho do período (a soma fecha com o total)
        $ativos = collect(array_keys($valorAtivo + $valorInicialAtivo + $fluxoAtivo))
            ->map(function ($acaoId) use ($acoes, $valorAtivo, $valorInicialAtivo, $fluxoAtivo) {
                $ini = $valorInicialAtivo[$acaoId] ?? 0.0;
                $fim = $valorAtivo[$acaoId] ?? 0.0;
                $f = $fluxoAtivo[$acaoId] ?? ['aportes' => 0.0, 'resgates' => 0.0, 'realizado' => 0.0];
                $ganho = $fim - $ini - ($f['aportes'] - $f['resgates']);
                $investido = $ini + $f['aportes'];
                $acao = $acoes[$acaoId];

                return [
                    'acao_id' => $acaoId,
                    'ticker' => $acao?->ticker,
                    'nome' => $acao?->nome,
                    'tipo' => $acao?->tipo,
                    'valor_inicial' => round($ini, 2),
                    'valor_final' => round($fim, 2),
                    'aportes' => round($f['aportes'], 2),
                    'resgates' => round($f['resgates'], 2),
                    'realizado' => round($f['realizado'], 2),
                    'ganho' => round($ganho, 2),
                    'ganho_pct' => $investido > 0 ? $ganho / $investido * 100 : null,
                ];
            })
            ->filter(fn ($a) => $a['valor_inicial'] > 0 || $a['valor_final'] > 0 || $a['aportes'] > 0 || $a['resgates'] > 0)
            ->sortByDesc('ganho')
            ->values()
            ->all();

        return ['de' => $de, 'ate' => $ate, 'base' => $base, 'pontos' => $pontos, 'resumo' => $resumo, 'ativos' => $ativos];
    }
}
