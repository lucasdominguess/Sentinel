<?php

namespace App\Clients;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SigaSoapProfissionalClient
{
    public function __construct(
        private string $url,
        private string $username,
        private string $password,
        private string $sistema
    ) {}

    public function enviar(string $xmlTemplate): string
    {
        // Substitui as credenciais no template
        $xml = str_replace(
            ['#USERNAME#', '#PASSWORD#', '#SISTEM#'],
            [$this->username, $this->password, $this->sistema],
            $xmlTemplate
        );

        // LOG IMPORTANTE: XML QUE SERÁ ENVIADO
        Log::info('SIGA - XML ENVIADO (RAW)', [
            'xml' => $xml,
        ]);

        $response = Http::withHeaders([
                'Content-Type' => 'text/xml; charset=UTF-8',
                'User-Agent'   => 'Mozilla/5.0',
            ])
            ->withOptions([
                'verify'        => false,
                'http_errors'  => false,
                'curl' => [
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_FOLLOWLOCATION => true,
                ],
            ])
            ->timeout(60)
            ->withBody($xml, 'text/xml')
            ->post($this->url);

        return $this->extrairXmlOuFault($response->body());
    }

    /**
     * Extrair o XML ou Fault (Para erros de negócio)
     * Ex: validação de cpf que vem do webservice
     * @param string $body
     * @throws \RuntimeException
     * @throws \DomainException
     * @return string
     */
    private function extrairXmlOuFault(string $body): string
    {
        // Extrai apenas o XML do multipart
        if (!preg_match('/(<\?xml.*<\/soapenv:Envelope>)/s', $body, $matches)) {
            throw new \RuntimeException('XML SOAP inválido');
        }

        $xml = $matches[1];

        // 👇 Detecta SOAP Fault
        if (str_contains($xml, '<soapenv:Fault>')) {

            if (preg_match('/<faultstring>(.*?)<\/faultstring>/s', $xml, $fault)) {
                $mensagem = trim($fault[1]);

                // Erro de negócio claro
                throw new \DomainException($mensagem);
            }

            throw new \DomainException('Erro retornado pelo SIGA');
        }

        return $xml;
    }
}