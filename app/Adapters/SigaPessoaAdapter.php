<?php

namespace App\Adapters;

use App\DTOs\Siga\PessoaSigaResponseDTO;
use App\Exceptions\Siga\SigaInvalidResponseException;
use Carbon\Carbon;

// Interpreta a resposta SOAP de pesquisarPorFiltro/pesquisar (namespaces smssp.fundacao.br).
// Robusto ao prefixo dinâmico ax## — navega pelas URIs dos namespaces, não pelos prefixos.
class SigaPessoaAdapter
{
    private const NS_SERVICE = 'http://service.smssp.fundacao.br';
    private const NS_DOMAIN = 'http://domain.smssp.fundacao.br/xsd';

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

        $xml->registerXPathNamespace('svc', self::NS_SERVICE);
        $returns = $xml->xpath('//svc:return') ?: [];

        $pessoas = [];

        foreach ($returns as $return) {
            $p = $return->children(self::NS_DOMAIN);

            $endereco = self::mapEndereco($p);

            $pessoas[] = new PessoaSigaResponseDTO(
                nome: self::str($p->nome),
                nomeSocial: self::str($p->nomeSocial),
                nomeMae: self::str($p->nomeMae),
                nomePai: self::str($p->nomePai),
                cpf: self::str($p->cpf->numero),
                cns: self::str($p->numeroCns),
                dataNascimento: self::date($p->dataNascimento),
                sexo: self::str($p->codigoSexoSus),
                email: self::str($p->contato->email),
                telefoneCelular: self::phone(
                    self::str($p->contato->dddCelular),
                    self::str($p->contato->telefoneCelular)
                ),
                telefoneResidencial: self::phone(
                    self::str($p->contato->dddResidencial),
                    self::str($p->contato->telefoneResidencial)
                ),
                endereco: $endereco,
            );
        }

        return $pessoas;
    }

    private static function mapEndereco(\SimpleXMLElement $p): ?array
    {
        $endereco = [
            'cep' => self::str($p->endereco->cep),
            'tipo_logradouro' => self::str($p->endereco->tipoLogradouro),
            'logradouro' => self::str($p->endereco->logradouro),
            'numero' => self::str($p->endereco->numeroResidencia),
            'complemento' => self::str($p->endereco->complemento),
            'bairro' => self::str($p->endereco->bairro),
            'codigo_municipio_residencia' => self::str($p->endereco->codigoMunicipioResidenciaSUS),
        ];

        // Se nenhum campo veio preenchido, não expõe o bloco de endereço.
        return array_filter($endereco) ? $endereco : null;
    }

    private static function phone(?string $ddd, ?string $numero): ?string
    {
        if (!$numero) {
            return null;
        }

        return $ddd ? $ddd . $numero : $numero;
    }

    private static function date($value): ?string
    {
        $raw = trim((string) $value);

        if ($raw === '') {
            return null;
        }

        try {
            return Carbon::parse($raw)->format('Y-m-d');
        } catch (\Throwable $e) {
            return $raw;
        }
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
