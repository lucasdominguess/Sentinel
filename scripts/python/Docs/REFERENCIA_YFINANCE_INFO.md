# Referência: campos do `Ticker.info` (yfinance)

Documento de apoio para entender **cada informação** retornada por
`yfinance.Ticker("TICKER.SA").info`.

O `.info` é um dicionário (`dict`) que corresponde ao *quote summary* da API do
Yahoo Finance. Nem todo ticker traz todos os campos — alguns vêm `None`, `0.0`
ou simplesmente não aparecem.

> Exemplos abaixo usam o ticker **ALPA3.SA** (Alpargatas), com a cotação
> **atrasada ~15 min** e o mercado em `PREPRE` (antes da abertura do pregão).

## Índice
1. [Identificação da empresa](#1-identificação-da-empresa)
2. [Contato e sede](#2-contato-e-sede)
3. [Preço e pregão do dia](#3-preço-e-pregão-do-dia)
4. [Faixas históricas](#4-faixas-históricas)
5. [Volume e liquidez](#5-volume-e-liquidez)
6. [Dividendos](#6-dividendos)
7. [Valuation (preço × fundamento)](#7-valuation-preço--fundamento)
8. [Saúde financeira e rentabilidade](#8-saúde-financeira-e-rentabilidade)
9. [Composição acionária](#9-composição-acionária)
10. [Recomendações de analistas](#10-recomendações-de-analistas)
11. [Metadados de mercado e câmbio](#11-metadados-de-mercado-e-câmbio)
12. [Datas e timestamps](#12-datas-e-timestamps)
13. [Pegadinhas importantes](#13-pegadinhas-importantes)
14. [Campos mais úteis para screening de small cap](#14-campos-mais-úteis-para-screening-de-small-cap)

---

## 1. Identificação da empresa

| Campo | Significado | Exemplo |
|---|---|---|
| `symbol` | Ticker do ativo | `ALPA3.SA` |
| `shortName` | Nome curto (como aparece no home broker) | `ALPARGATAS ON N1` |
| `longName` | Razão social | `Alpargatas S.A.` |
| `quoteType` | Tipo de ativo (`EQUITY`, `ETF`, `INDEX`, `CRYPTOCURRENCY`...) | `EQUITY` |
| `typeDisp` | Tipo de ativo "para exibição" | `Equity` |
| `longBusinessSummary` | Descrição textual do negócio | "manufactures and sells footwear..." |
| `sector` / `sectorDisp` / `sectorKey` | Setor econômico (chave e texto) | `Consumer Cyclical` |
| `industry` / `industryDisp` / `industryKey` | Segmento dentro do setor | `Footwear & Accessories` |
| `companyOfficers` | Lista de executivos: `name`, `title`, `age`, `exercisedValue`, `unexercisedValue` | CEO Liel Miranda, etc. |
| `executiveTeam` | Time executivo (costuma vir vazio para ações .SA) | `[]` |
| `messageBoardId` | ID interno do fórum do Yahoo | `finmb_874525` |
| `maxAge` | Tempo de cache do dado (segundos) | `86400` |

## 2. Contato e sede

| Campo | Significado | Exemplo |
|---|---|---|
| `address1` / `address2` | Endereço | `Av. das Nações Unidas, 14.261` |
| `city` / `state` / `zip` / `country` | Cidade / estado / CEP / país | `São Paulo` / `SP` / `04794-000` / `Brazil` |
| `phone` | Telefone | `55 11 3847 7322` |
| `website` | Site institucional | `https://www.alpargatas.com.br` |

## 3. Preço e pregão do dia

| Campo | Significado | Exemplo |
|---|---|---|
| `currentPrice` | Preço atual | `12.6` |
| `regularMarketPrice` | Preço no pregão regular (= `currentPrice` na maioria dos casos) | `12.6` |
| `previousClose` / `regularMarketPreviousClose` | Fechamento do pregão anterior | `13.39` |
| `open` / `regularMarketOpen` | Abertura do dia | `0.0` ⚠️ |
| `dayLow` / `regularMarketDayLow` | Mínima do dia | `0.0` ⚠️ |
| `dayHigh` / `regularMarketDayHigh` | Máxima do dia | `0.0` ⚠️ |
| `regularMarketDayRange` | Faixa do dia (texto) | `"0.0 - 0.0"` |
| `regularMarketChange` | Variação do dia (R$) | `-0.79` |
| `regularMarketChangePercent` | Variação do dia (%) | `-5.9` |
| `fulldayPrice` / `fulldayChange` / `fulldayChangePercent` | Preço e variação do "dia cheio" | `12.6` / `-0.79` / `-5.9` |
| `bid` / `ask` | Melhor oferta de compra / venda | `12.61` / `12.66` |
| `bidSize` / `askSize` | Quantidade nas ofertas | `0` / `0` |
| `fiftyDayAverage` | Média móvel de 50 dias | `10.39` |
| `twoHundredDayAverage` | Média móvel de 200 dias | `10.64` |
| `fiftyDayAverageChange` / `...Percent` | Distância do preço à média de 50 dias | `2.21` / `21.2%` |
| `twoHundredDayAverageChange` / `...Percent` | Distância do preço à média de 200 dias | `1.96` / `18.4%` |
| `priceHint` | Nº de casas decimais sugeridas | `2` |
| `tradeable` / `triggerable` / `cryptoTradeable` | Flags de negociabilidade do Yahoo | `False` |
| `hasPrePostMarketData` | Se há dados de pré/pós-mercado | `False` |

> ⚠️ `open`, `dayLow`, `dayHigh` e `volume` vêm `0.0` quando o pregão ainda
> não abriu (`marketState` = `PREPRE`/`PRE`/`CLOSED`). Veja a seção
> [Pegadinhas](#13-pegadinhas-importantes).

## 4. Faixas históricas

| Campo | Significado | Exemplo |
|---|---|---|
| `fiftyTwoWeekLow` | Mínima das últimas 52 semanas | `0.0` ⚠️ |
| `fiftyTwoWeekHigh` | Máxima das últimas 52 semanas | `13.49` |
| `fiftyTwoWeekRange` | Faixa de 52 semanas (texto) | `"0.0 - 13.49"` |
| `fiftyTwoWeekLowChange` / `...` | Distância do preço à mínima de 52 sem. | `12.6` |
| `fiftyTwoWeekHighChange` / `...Percent` | Distância do preço à máxima de 52 sem. | `-0.89` / `-6.6%` |
| `fiftyTwoWeekChangePercent` | Variação acumulada em 52 semanas (%) | `26.5` |
| `52WeekChange` | Mesma variação, em fração | `0.265` |
| `SandP52WeekChange` | Variação do S&P 500 no mesmo período (comparativo) | `0.151` |
| `allTimeHigh` | Máxima histórica | `53.39` |
| `allTimeLow` | Mínima histórica | `0.30` |

## 5. Volume e liquidez

| Campo | Significado | Exemplo |
|---|---|---|
| `volume` / `regularMarketVolume` | Volume negociado no dia | `0` ⚠️ |
| `averageVolume` | Volume médio diário (geral) | `12.761` |
| `averageVolume10days` / `averageDailyVolume10Day` | Volume médio dos últimos 10 dias | `15.580` |
| `averageDailyVolume3Month` | Volume médio dos últimos 3 meses | `12.761` |

> Para **small caps** esse bloco é essencial: volume baixo = baixa liquidez
> (difícil entrar/sair sem impactar o preço).

## 6. Dividendos

| Campo | Significado | Exemplo |
|---|---|---|
| `dividendRate` | Dividendo anual estimado (R$/ação) | `0.49` |
| `dividendYield` | Dividend yield — **já em %** nas versões novas | `3.9` ⚠️ |
| `fiveYearAvgDividendYield` | DY médio dos últimos 5 anos | `0.43` |
| `payoutRatio` | % do lucro distribuído em dividendos (fração) | `0.4936` (≈49%) |
| `lastDividendValue` | Valor do último dividendo pago (R$/ação) | `0.4918` |
| `lastDividendDate` | Data do último dividendo (timestamp) | `1765929600` |
| `exDividendDate` | Data ex-dividendo (timestamp) | `1765929600` |
| `trailingAnnualDividendRate` | Dividendos pagos nos últimos 12 meses (R$) | `0.0` ⚠️ |
| `trailingAnnualDividendYield` | DY dos últimos 12 meses | `0.0` ⚠️ |

> ⚠️ **Escala do `dividendYield`**: nas versões recentes do `yfinance` ele vem
> em **porcentagem** (`3.9` = 3,9%). Já `payoutRatio` e `fiveYearAvgDividendYield`
> vêm como **fração**. Não multiplique o `dividendYield` por 100.

## 7. Valuation (preço × fundamento)

| Campo | Significado | Exemplo |
|---|---|---|
| `marketCap` | Valor de mercado (preço × nº de ações) | `8.56 bi` |
| `nonDilutedMarketCap` | Market cap sem diluição | `8.15 bi` |
| `enterpriseValue` | Enterprise Value / Valor da firma (market cap + dívida − caixa) | `9.24 bi` |
| `trailingPE` | P/L — Preço / Lucro dos últimos 12 meses | `12.6` |
| `priceToBook` | P/VP — Preço / Valor Patrimonial | `2.36` |
| `bookValue` | Valor patrimonial por ação (VPA) | `5.33` |
| `priceToSalesTrailing12Months` | P/Receita (Price-to-Sales) | `1.77` |
| `enterpriseToRevenue` | EV / Receita | `1.92` |
| `enterpriseToEbitda` | EV / EBITDA | `11.15` |
| `pegRatio` | P/L ajustado ao crescimento | `0.55` |
| `trailingPegRatio` | PEG dos últimos 12 meses | `None` |
| `trailingEps` / `epsTrailingTwelveMonths` | Lucro por ação (LPA) dos últimos 12 meses | `1.0` |

## 8. Saúde financeira e rentabilidade

| Campo | Significado | Exemplo |
|---|---|---|
| `totalRevenue` | Receita total | `4.83 bi` |
| `revenuePerShare` | Receita por ação | `7.11` |
| `ebitda` | EBITDA | `829 mi` |
| `ebitdaMargins` | Margem EBITDA (fração) | `0.172` (17,2%) |
| `grossProfits` | Lucro bruto | `2.56 bi` |
| `grossMargins` | Margem bruta (fração) | `0.530` (53%) |
| `operatingMargins` | Margem operacional (fração) | `0.171` (17,1%) |
| `profitMargins` | Margem líquida (fração) | `0.145` (14,5%) |
| `netIncomeToCommon` | Lucro líquido atribuído aos acionistas | `701 mi` |
| `returnOnEquity` | ROE — Retorno sobre Patrimônio | `0.181` (18,1%) |
| `returnOnAssets` | ROA — Retorno sobre Ativos | `0.076` (7,6%) |
| `totalCash` | Caixa total | `718 mi` |
| `totalCashPerShare` | Caixa por ação | `1.06` |
| `totalDebt` | Dívida total | `1.4 bi` |
| `debtToEquity` | Dívida / Patrimônio Líquido | `38.72` |
| `quickRatio` | Liquidez seca | `1.36` |
| `currentRatio` | Liquidez corrente | `1.91` |
| `freeCashflow` | Fluxo de caixa livre (FCF) | `389 mi` |
| `operatingCashflow` | Fluxo de caixa operacional | `789 mi` |
| `earningsGrowth` | Crescimento do lucro | `0.928` (92,8%) |
| `revenueGrowth` | Crescimento da receita | `0.113` (11,3%) |
| `earningsQuarterlyGrowth` | Crescimento trimestral do lucro (YoY) | `0.93` |
| `financialCurrency` | Moeda dos demonstrativos | `BRL` |

## 9. Composição acionária

| Campo | Significado | Exemplo |
|---|---|---|
| `sharesOutstanding` | Nº de ações em circulação | `339,5 mi` |
| `impliedSharesOutstanding` | Nº de ações implícito (inclui todas as classes) | `679,3 mi` |
| `floatShares` | Free float — ações efetivamente negociáveis | `133 mi` |
| `heldPercentInsiders` | % em poder de controladores/insiders | `0.893` (89,3%) |
| `heldPercentInstitutions` | % em poder de institucionais | `0.083` (8,3%) |
| `lastSplitFactor` | Fator do último desdobramento/grupamento | `"125:100"` |
| `lastSplitDate` | Data do último split (timestamp) | `1553126400` |

> `heldPercentInsiders` alto (89%) explica o volume baixo: sobra pouca ação
> circulando no mercado.

## 10. Recomendações de analistas

| Campo | Significado | Exemplo |
|---|---|---|
| `recommendationKey` | Recomendação (`strong_buy`, `buy`, `hold`, `sell`, `strong_sell`) | `strong_buy` |
| `recommendationMean` | Nota média (1 = strong buy … 5 = strong sell) | `1.0` |
| `averageAnalystRating` | Rating em texto | `"1.0 - Strong Buy"` |
| `numberOfAnalystOpinions` | Nº de analistas que cobrem o ativo | `1` ⚠️ |
| `targetHighPrice` | Preço-alvo mais otimista | `20.0` |
| `targetLowPrice` | Preço-alvo mais pessimista | `20.0` |
| `targetMeanPrice` | Preço-alvo médio | `20.0` |
| `targetMedianPrice` | Preço-alvo mediano | `20.0` |

> ⚠️ Com **apenas 1 analista**, as recomendações e preços-alvo têm baixa
> representatividade — comum em small caps.

## 11. Metadados de mercado e câmbio

| Campo | Significado | Exemplo |
|---|---|---|
| `currency` | Moeda da cotação | `BRL` |
| `exchange` | Código da bolsa | `SAO` |
| `fullExchangeName` | Nome da bolsa | `São Paulo` |
| `market` | Mercado | `br_market` |
| `marketState` | Estado do mercado (`PRE`, `PREPRE`, `REGULAR`, `POST`, `CLOSED`) | `PREPRE` |
| `exchangeTimezoneName` | Fuso da bolsa | `America/Sao_Paulo` |
| `exchangeTimezoneShortName` | Fuso (abreviado) | `BRT` |
| `gmtOffSetMilliseconds` | Offset do fuso em ms | `-10800000` |
| `region` / `language` | Região / idioma do dado | `US` / `en-US` |
| `quoteSourceName` | Origem da cotação | `Delayed Quote` |
| `sourceInterval` | Intervalo da fonte (min) | `15` |
| `exchangeDataDelayedBy` | Atraso dos dados (min) | `15` ⚠️ |
| `beta` | Beta — volatilidade vs. mercado (1 = igual ao mercado) | `0.56` |
| `customPriceAlertConfidence` | Confiança do alerta de preço do Yahoo | `LOW` |
| `corporateActions` | Eventos corporativos recentes | `[]` |
| `esgPopulated` | Se há dados ESG | `False` |

> ⚠️ `exchangeDataDelayedBy: 15` → a cotação **não é em tempo real**, tem
> ~15 min de atraso. Importante se você for tomar decisão intradiária.

## 12. Datas e timestamps

Vêm como **Unix timestamp** (segundos desde 01/01/1970), exceto onde indicado.

| Campo | Significado |
|---|---|
| `lastFiscalYearEnd` | Fim do último ano fiscal |
| `nextFiscalYearEnd` | Fim do próximo ano fiscal |
| `mostRecentQuarter` | Fim do trimestre mais recente |
| `exDividendDate` / `lastDividendDate` | Data ex-dividendo / último dividendo |
| `lastSplitDate` | Data do último split |
| `earningsTimestampStart` / `earningsTimestampEnd` | Janela estimada de divulgação de resultados |
| `earningsCallTimestampStart` / `...End` | Janela da teleconferência de resultados |
| `isEarningsDateEstimate` | Se a data de resultado é estimada |
| `regularMarketTime` | Horário da última cotação |
| `firstTradeDateMilliseconds` | Data do 1º pregão — **em milissegundos** ⚠️ |

**Convertendo para data legível (Python):**

```python
from datetime import datetime

def format_date(timestamp):
    if not timestamp:
        return None
    return datetime.fromtimestamp(timestamp).strftime("%d/%m/%Y")

# Timestamp em SEGUNDOS:
format_date(info.get("exDividendDate"))

# firstTradeDateMilliseconds está em MILISSEGUNDOS — divida por 1000:
format_date(info.get("firstTradeDateMilliseconds") / 1000)
```

## 13. Pegadinhas importantes

1. **Campos que "zeram" fora do pregão.** Com `marketState` em
   `PREPRE`/`PRE`/`CLOSED`, estes vêm `0.0`:
   `open`, `dayLow`, `dayHigh`, `volume`, `regularMarketVolume` e às vezes
   `fiftyTwoWeekLow`. Para mínima/máxima confiáveis, prefira o histórico:
   ```python
   hist = Ticker.history(start="2026-10-01")
   max_mes = hist["High"].max()
   min_mes = hist["Low"].min()
   ```

2. **Escalas diferentes.** `dividendYield` vem em **porcentagem** (`3.9`),
   mas `payoutRatio`, `fiveYearAvgDividendYield` e todas as margens
   (`grossMargins`, `profitMargins`...) vêm como **fração** (`0.49`).
   Confira a escala antes de exibir.

3. **Timestamps em unidades diferentes.** Quase tudo em segundos, mas
   `firstTradeDateMilliseconds` em milissegundos.

4. **Campos podem não existir / vir `None`.** Sempre use
   `info.get("campo")` em vez de `info["campo"]` para não quebrar o script.

5. **Dados atrasados.** `exchangeDataDelayedBy: 15` → cotação com ~15 min de
   atraso; não serve para operação em tempo real.

6. **`trailingAnnualDividend*` pouco confiável para .SA** — costuma vir `0.0`.
   Prefira `dividendRate` + `lastDividendValue`.

7. **`.info` é "pesado" e instável.** Faz uma requisição grande ao Yahoo e o
   layout dos campos muda entre versões do `yfinance`. Para poucos campos,
   `Ticker.fast_info` é mais leve e rápido.

## 14. Campos mais úteis para screening de small cap

| Objetivo | Campos |
|---|---|
| **Liquidez** | `averageVolume`, `averageDailyVolume3Month`, `floatShares`, `heldPercentInsiders` |
| **Preço justo (valuation)** | `trailingPE`, `priceToBook`, `enterpriseToEbitda`, `pegRatio` |
| **Rentabilidade** | `returnOnEquity`, `profitMargins`, `operatingMargins` |
| **Endividamento** | `debtToEquity`, `currentRatio`, `totalDebt`, `totalCash` |
| **Dividendos** | `dividendYield`, `dividendRate`, `payoutRatio`, `lastDividendValue`, `exDividendDate` |
| **Crescimento** | `revenueGrowth`, `earningsGrowth`, `earningsQuarterlyGrowth` |
| **Momento de preço** | `fiftyDayAverage`, `twoHundredDayAverage`, `fiftyTwoWeekHigh/Low` |

---

*Gerado a partir do retorno de `yfinance.Ticker("ALPA3.SA").info`. Os valores de
exemplo são ilustrativos e mudam a cada pregão. Para a lista oficial de campos,
consulte a documentação do [yfinance](https://ranaroussi.github.io/yfinance/).*
