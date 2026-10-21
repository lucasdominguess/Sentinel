<?php

namespace Tests\Feature\Console;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PostmanGenerateTest extends TestCase
{
    /**
     * Prepara o ambiente antes de CADA teste.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // 1. Limpa qualquer arquivo gerado anteriormente para não dar falso positivo
        if (File::exists(storage_path('app/postman_collection.json'))) {
            File::delete(storage_path('app/postman_collection.json'));
        }

        // 2. Registramos algumas rotas "fakes" exclusivas para esse teste rodar
        Route::get('api/users', function () { return 'users'; });
        Route::post('api/auth/login', function () { return 'login'; });
        Route::get('site/sobre', function () { return 'sobre'; }); // Rota Web (deve ser ignorada)
    }

    /**
     * Limpa a "sujeira" depois que o teste acaba.
     */
    protected function tearDown(): void
    {
        if (File::exists(storage_path('app/postman_collection.json'))) {
            File::delete(storage_path('app/postman_collection.json'));
        }
        
        parent::tearDown();
    }

    /**
     * Teste principal: Verifica se o comando roda e gera o arquivo corretamente.
     */
    public function test_gera_collection_postman_padrao()
    {
        // Aqui nós simulamos a execução e as respostas do usuário!
        $this->artisan('postman:generate')
             ->expectsOutput('Iniciando geração da collection...')
             ->expectsConfirmation('Deseja definir um nome personalizado?', false) // Responde "Não"
             ->expectsConfirmation('A API usa Bearer Token?', true) // Responde "Sim"
             ->expectsConfirmation('Deseja incluir rotas do Swagger?', false) // Responde "Não"
             ->expectsQuestion('Digite o host', 'http://localhost') // Digita o host
             ->expectsQuestion('Digite a porta', '8000') // Digita a porta
             ->assertSuccessful(); // Garante que o comando terminou com código 0 (sucesso)

        // 1. Assert: Verifica se o arquivo foi fisicamente criado onde deveria
        $caminhoArquivo = storage_path('app/postman_collection.json');
        $this->assertFileExists($caminhoArquivo);

        // 2. Assert: Lê o JSON gerado e decodifica para um array PHP
        $conteudo = json_decode(file_get_contents($caminhoArquivo), true);

        // 3. Assert: Verifica se o nome da collection puxou do .env (já que respondemos "false" pro nome customizado)
        $this->assertEquals(config('app.name'), $conteudo['info']['name']);

        // 4. Assert: Verifica se ele ignorou a rota web e capturou só as de API
        // O comando agrupa por pastas. Vamos pegar os nomes das pastas geradas.
        $pastasGeradas = array_column($conteudo['item'], 'name');
        
        $this->assertContains('Users', $pastasGeradas);
        $this->assertContains('Auth', $pastasGeradas);
        $this->assertNotContains('Site', $pastasGeradas); // Garante que a rota web não entrou
        
        // 5. Assert: Verifica se a variável base_url foi montada certa
        $variaveis = $conteudo['variable'];
        $this->assertEquals('base_url', $variaveis[0]['key']);
        $this->assertEquals('http://localhost:8000', $variaveis[0]['value']);
    }
}