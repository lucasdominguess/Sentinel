<?php

namespace App\Builders;

use Carbon\Carbon;

// Monta o XML SOAP da operação pesquisarPorFiltro (WebService PessoaService).
// Placeholders #USERNAME#/#PASSWORD#/#SISTEM# são substituídos pelo SigaSoapPessoaClient.
class SigaPessoaXmlBuilder
{
    public static function pesquisarPorFiltro(array $params): string
    {
        $filtros = [];

        if (!empty($params['cpf'])) {
            $filtros[] = self::tag('cpf', $params['cpf']);
        }

        if (!empty($params['cns'])) {
            $filtros[] = self::tag('numeroCns', $params['cns']);
        }

        if (!empty($params['nome'])) {
            $filtros[] = self::tag('nome', $params['nome']);
        }

        if (!empty($params['nomeMae'])) {
            $filtros[] = self::tag('nomeMae', $params['nomeMae']);
        }

        if (!empty($params['dataNascimento'])) {
            // SIGA espera a data no formato ISO com timezone
            $data = Carbon::parse($params['dataNascimento'])->format('Y-m-d\T00:00:00.000-03:00');
            $filtros[] = self::tag('dataNascimento', $data);
        }

        if (empty($filtros)) {
            throw new \InvalidArgumentException('Informe ao menos um parâmetro de pesquisa');
        }

        $filtroXml = implode("\n                ", $filtros);

        return <<<XML
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
                          xmlns:ser="http://service.smssp.fundacao.br"
                          xmlns:xsd="http://domain.smssp.fundacao.br/xsd"
                          xmlns:ns="http://auth.smssp.atech.br">
            <soapenv:Header>
                <ns:login>#USERNAME#</ns:login>
                <ns:password>#PASSWORD#</ns:password>
                <ns:sistema>#SISTEM#</ns:sistema>
            </soapenv:Header>
            <soapenv:Body>
                <ser:pesquisarPorFiltro>
                    <ser:filtroPesquisaPessoa>
                        {$filtroXml}
                    </ser:filtroPesquisaPessoa>
                </ser:pesquisarPorFiltro>
            </soapenv:Body>
        </soapenv:Envelope>
        XML;
    }

    // Campos internos do filtro usam o namespace de domínio (xsd:), conforme exemplo oficial.
    private static function tag(string $name, string $value): string
    {
        $safe = htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return "<xsd:{$name}>{$safe}</xsd:{$name}>";
    }
}
