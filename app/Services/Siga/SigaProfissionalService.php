<?php

namespace App\Services\Siga;

use App\Adapters\SigaProfissionalAdapter;
use App\Builders\SigaProfissionalXmlBuilder;
use App\Clients\SigaSoapProfissionalClient;
use App\Contracts\Services\Siga\SigaProfissionalServiceInterface;
use App\DTOs\Siga\BuscarProfissionalSigaDTO;
use App\Exceptions\Siga\SigaBusinessException;
use App\Exceptions\Siga\SigaInvalidResponseException;
use App\Exceptions\Siga\SigaUnavailableException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;

class SigaProfissionalService implements SigaProfissionalServiceInterface
{
    public function __construct(
        private SigaSoapProfissionalClient $client
    ) {}

    public function buscarProfissional(BuscarProfissionalSigaDTO $dto): array
    {
        $xml = SigaProfissionalXmlBuilder::pesquisarProfissional($dto->toArray());

        try {
            $resposta = $this->client->enviar($xml);
        } catch (ConnectionException $e) {
            Log::error('WebService SIGA Profissional indisponível: ' . $e->getMessage());
            throw new SigaUnavailableException();
        } catch (\DomainException $e) {
            // SOAP Fault / validação de negócio do próprio SIGA (ex.: CPF inválido).
            throw new SigaBusinessException($e->getMessage());
        } catch (\RuntimeException $e) {
            Log::error('Resposta inválida do SIGA Profissional: ' . $e->getMessage());
            throw new SigaInvalidResponseException();
        }

        return SigaProfissionalAdapter::fromSoap($resposta);
    }
}
