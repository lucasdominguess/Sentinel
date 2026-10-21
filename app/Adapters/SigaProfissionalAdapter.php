<?php

namespace App\Adapters;

use App\DTOs\Siga\ProfissionalVinculoResponseDTO;
use App\Exceptions\Siga\SigaInvalidResponseException;

// Interpreta a resposta SOAP de pesquisarProfissional (namespaces PRODAM).
// Robusto ao prefixo dinâmico ax### — navega pela URI do namespace de domínio, não pelo prefixo.
class SigaProfissionalAdapter
{
    private const NS_DOMAIN = 'http://domain.prodam.sp.gov.br/xsd';

    public static function fromSoap(string $soapResponse): array
    {
        $envelope = self::extractEnvelope($soapResponse);

        // Suprime warnings do libxml e trata erro de parse de forma controlada.
        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($envelope);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($xml === false) {
            throw new SigaInvalidResponseException(
                'Não foi possível interpretar o XML retornado pelo SIGA.'
            );
        }

        $xml->registerXPathNamespace('dom', self::NS_DOMAIN);
        $vinculos = $xml->xpath('//dom:vinculosProfissional') ?: [];

        $result = [];

        foreach ($vinculos as $vinculo) {
            $campos = $vinculo->children(self::NS_DOMAIN);

            $result[] = ProfissionalVinculoResponseDTO::fromArray([
                'cnsProfissional' => self::str($campos->cnsProfissional),
                'cpfProfissional' => self::str($campos->cpfProfissional),
                'nomeProfissional' => self::str($campos->nomeProfissional),
                'codigoCboSus' => self::str($campos->codigoCboSus),
                'descricaoCboSus' => self::str($campos->descricaoCboSus),
                'codigoVinculo' => self::str($campos->codigoVinculo),
                'dataInicio' => self::str($campos->dataInicio),
                'dataFim' => self::str($campos->dataFim),
                'dataUltimaAlteracao' => self::str($campos->dataUltimaAlteracao),
                'codigoEquipe' => self::str($campos->codigoEquipe),
            ]);
        }

        return $result;
    }

    private static function str($value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private static function extractEnvelope(string $body): string
    {
        // Remove eventual wrapper MIME/multipart, mantendo apenas o Envelope SOAP.
        if (preg_match('/<(\w+:)?Envelope\b.*<\/(\w+:)?Envelope>/s', $body, $matches)) {
            return $matches[0];
        }

        return $body;
    }
}
