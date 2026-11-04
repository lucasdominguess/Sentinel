# Sentinel — sistema visual v1 (tela: Ações monitoradas)

## Arquivos
| Camada | Arquivo | Reaproveitável? |
|---|---|---|
| Tokens | `resources/css/tokens.css` | Sistema |
| Componentes | `resources/css/components.css` | Sistema |
| Tela de lista | `resources/css/acoes.css` | Lista de ativos (Ações, FIIs, ETFs) |
| JS | `resources/js/sentinel.js` | Sistema (tema, drawer, busca, “/”, flash) |
| Layout | `views/layouts/app.blade.php` | Sistema (topbar + nav + drawer) |
| Tela | `views/acoes/index.blade.php` | Específico |
| Tela | `views/aportes/ativos.blade.php` + `css/carteira.css` | Específico (Meus ativos) |
| Dataviz | `donut`, `cost-bar` | Sistema |
| Posição | `holding-card`, `holding-row` | Específico (Meus ativos) |
| Componentes Blade | `asset-card`, `asset-row`, `sparkline`, `range-bar`, `badge-var` | Sistema (sparkline/badge servem em qualquer tela) |

## Padrão de tela (repetir nas próximas)
1. `.page-header` → `.kicker` + `.h1` à esquerda, `.stats` à direita.
2. `.toolbar` → `.seg` à esquerda, `.search` / ações à direita. Altura de controles = `--control-h` (40px).
3. Conteúdo em `.card`; grade `repeat(auto-fill, minmax(256px,1fr))`, gap `--space-4`.
4. Estados: `.skeleton` (carregando), `.empty` (vazio), `.alert--error` (erro).

## Regras
- Todo número: `font-variant-numeric: tabular-nums`.
- Alta/queda: sempre cor + ▲/▼ + texto `sr-only`.
- Acento (azul no claro, roxo no escuro) só em: kicker, item ativo de nav/seg, foco, CTA primária, estado vazio.
- Breakpoints: 375 base · 640 (busca em linha) · 768 (padding 24) · 1024 (nav no topo, lista densa).
- z-index: topbar 40, drawer 60.

## Contrato de dados (controller)
```php
$ativos = Acao::monitoradas()->ordenar($ordem)->busca($q)->get()
  ->map(fn ($a) => (object) [
    'ticker' => 'VALE3', 'nome' => 'Vale S.A.',
    'preco' => 61.67, 'variacao' => 1.62, 'variacao_pct' => 2.70,
    'min' => 59.42, 'max' => 62.97, 'fech_ant' => 60.05, 'abertura' => 59.44,
    'serie' => [/* preços intradia, ~40–80 pontos */],
  ]);
```
### Meus ativos
```php
// cor: top 5 por valor_atual recebem var(--cat-1..5), o resto var(--cat-rest)
// peso: valor_atual / total da carteira * 100
$alocacao['ativo'] = [['label'=>'VGIR11','value'=>10540.35,'color'=>'var(--cat-1)'], /* … */ ['label'=>'Outros','value'=>507.52,'color'=>'var(--cat-rest)']];
```

Se não houver série intradia, `<x-sparkline>` não renderiza nada e o card continua íntegro.
