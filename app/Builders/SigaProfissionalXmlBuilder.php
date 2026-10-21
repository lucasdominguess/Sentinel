<?php

namespace App\Builders;

// Monta o XML SOAP da operação pesquisarProfissional (WebService ProfissionalService).
// Placeholders #USERNAME#/#PASSWORD#/#SISTEM# são substituídos pelo SigaSoapProfissionalClient.
class SigaProfissionalXmlBuilder
{
    public static function pesquisarProfissional(array $params): string
    {
        $filtros = [];

        if (!empty($params['cmes'])) {
            $filtros[] = self::tag('cmesEstabelecimento', $params['cmes']);
        }

        if (!empty($params['cns'])) {
            $filtros[] = self::tag('cnsProfissional', $params['cns']);
        }

        if (!empty($params['cpf'])) {
            $filtros[] = self::tag('cpfProfissional', $params['cpf']);
        }

        if (!empty($params['data_inicio'])) {
            $filtros[] = self::tag('dataHoraInicioUltimaAtualizacao', $params['data_inicio']);
        }

        if (!empty($params['data_fim'])) {
            $filtros[] = self::tag('dataHoraFimUltimaAtualizacao', $params['data_fim']);
        }

        if (empty($filtros)) {
            throw new \InvalidArgumentException('Informe ao menos um parâmetro de pesquisa');
        }

        $filtroXml = implode("\n            ", $filtros);

        return <<<XML
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
                          xmlns:ser="http://service.prodam.sp.gov.br"
                          xmlns:ns="http://auth.smssp.atech.br">
            <soapenv:Header>
                <ns:login>#USERNAME#</ns:login>
                <ns:password>#PASSWORD#</ns:password>
                <ns:sistema>#SISTEM#</ns:sistema>
            </soapenv:Header>
            <soapenv:Body>
                <ser:pesquisarProfissional>
                    {$filtroXml}
                </ser:pesquisarProfissional>
            </soapenv:Body>
        </soapenv:Envelope>
        XML;
    }

    private static function tag(string $name, string $value): string
    {
        $safe = htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return "<ser:{$name}>{$safe}</ser:{$name}>";
    }
}
