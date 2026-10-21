<?php

namespace App\Services\Siga;

use App\Adapters\SigaPessoaAdapter;
use App\Builders\SigaPessoaXmlBuilder;
use App\Clients\SigaSoapPessoaClient;
use App\Contracts\Services\Siga\SigaPessoaServiceInterface;
use App\DTOs\Siga\BuscarPessoaSigaDTO;
use App\Exceptions\Siga\SigaBusinessException;
use App\Exceptions\Siga\SigaInvalidResponseException;
use App\Exceptions\Siga\SigaUnavailableException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;

class SigaPessoaService implements SigaPessoaServiceInterface
{
    public function __construct(
        private SigaSoapPessoaClient $client
    ) {}

    public function buscarPessoa(BuscarPessoaSigaDTO $dto): array
    {
        $xml = SigaPessoaXmlBuilder::pesquisarPorFiltro($dto->toArray());

        try {
            $resposta = $this->client->enviar($xml);
        } catch (ConnectionException $e) {
            Log::error('WebService SIGA Pessoa indisponível: ' . $e->getMessage());
            throw new SigaUnavailableException();
        } catch (\DomainException $e) {
            // SOAP Fault / validação de negócio do próprio SIGA (ex.: CPF inválido).
            throw new SigaBusinessException($e->getMessage());
        } catch (\RuntimeException $e) {
            Log::error('Resposta inválida do SIGA Pessoa: ' . $e->getMessage());
            throw new SigaInvalidResponseException();
        }

        return SigaPessoaAdapter::fromSoap($resposta);
    }
}
