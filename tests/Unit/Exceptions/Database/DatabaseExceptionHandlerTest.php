<?php

namespace Tests\Unit\Exceptions\Database;

use App\Exceptions\Handlers\DatabaseExceptionHandler;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use PDOException;
use Tests\TestCase;

class DatabaseExceptionHandlerTest extends TestCase
{
    /**
     * Helper para criar uma QueryException simulando o formato do PostgreSQL.
     */
    private function createPostgresException(string $sqlState): QueryException
    {
        $pdoException = new PDOException("Simulated PDO Error");
        // O índice 0 no array errorInfo representa o SQLSTATE no Postgres
        $pdoException->errorInfo = [$sqlState, 0, 'Driver specific error message'];

        return new QueryException('pgsql', 'INSERT INTO table (...)', [], $pdoException);
    }

    /**
     * Helper para criar uma Request de API (que espera JSON).
     */
    private function createApiRequest(): Request
    {
        $request = Request::create('/api/test', 'POST');
        $request->headers->set('Accept', 'application/json');
        return $request;
    }

    public function test_deve_ignorar_requisicoes_web_comuns_retornando_false(): void
    {
        $exception = $this->createPostgresException('23505');
        
        // Requisição normal, sem header 'Accept: application/json'
        $request = Request::create('/test', 'GET');
        
        $response = DatabaseExceptionHandler::handle($exception, $request);

        $this->assertFalse($response);
    }

    public function test_deve_retornar_409_para_violacao_de_registro_duplicado(): void
    {
        $exception = $this->createPostgresException('23505');
        $response = DatabaseExceptionHandler::handle($exception, $this->createApiRequest());

        $this->assertEquals(409, $response->getStatusCode());
        $this->assertEquals('unique_violation', $response->getData()->code);
    }

    public function test_deve_retornar_422_para_violacao_de_chave_estrangeira(): void
    {
        $exception = $this->createPostgresException('23503');
        $response = DatabaseExceptionHandler::handle($exception, $this->createApiRequest());

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('foreign_key_violation', $response->getData()->code);
    }

    public function test_deve_retornar_422_para_campos_obrigatorios_nulos(): void
    {
        $exception = $this->createPostgresException('23502');
        $response = DatabaseExceptionHandler::handle($exception, $this->createApiRequest());

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('not_null_violation', $response->getData()->code);
    }

    public function test_deve_retornar_422_quando_o_texto_exceder_o_limite_da_coluna(): void
    {
        $exception = $this->createPostgresException('22001');
        $response = DatabaseExceptionHandler::handle($exception, $this->createApiRequest());

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('string_data_right_truncation', $response->getData()->code);
    }

    public function test_deve_retornar_500_para_falha_de_conexao(): void
    {
        $exception = $this->createPostgresException('08006');
        $response = DatabaseExceptionHandler::handle($exception, $this->createApiRequest());

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('connection_exception', $response->getData()->code);
    }

    public function test_deve_retornar_500_como_fallback_para_erros_desconhecidos(): void
    {
        // Um SQLSTATE inventado
        $exception = $this->createPostgresException('99999');
        $response = DatabaseExceptionHandler::handle($exception, $this->createApiRequest());

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertObjectHasProperty('error', $response->getData());
        $this->assertEquals('Ocorreu um erro interno no banco de dados.', $response->getData()->message);
    }
}