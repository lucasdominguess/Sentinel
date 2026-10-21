<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\XssCleanService;

class XssCleanServiceTest extends TestCase
{
    protected XssCleanService $xssService;

    /**
     * O setUp roda antes de cada teste.
     * Como a classe não depende de nada do Laravel, nós simplesmente
     * instanciamos ela com um "new" clássico do PHP.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->xssService = new XssCleanService();
    }

    public function test_deve_retornar_a_mesma_string_se_nao_houver_codigo_malicioso()
    {
        $textoLimpo = 'Olá, meu nome é João e eu gosto de PHP!';
        
        $resultado = $this->xssService->clean($textoLimpo);
        
        // Garante que o texto intacto foi retornado
        $this->assertEquals($textoLimpo, $resultado);
    }

    public function test_deve_neutralizar_string_com_script_malicioso()
    {
        $textoComXss = 'Texto normal <script>alert("Fui hackeado!");</script>';
        
        $resultado = $this->xssService->clean($textoComXss);
        
        // A biblioteca AntiXSS costuma remover as tags perigosas.
        // O assertNotEquals garante que o resultado é diferente da entrada maliciosa.
        $this->assertNotEquals($textoComXss, $resultado);
        // Garante que a tag script sumiu
        $this->assertStringNotContainsString('<script>', $resultado);
    }

    public function test_deve_substituir_por_vazio_quando_achar_xss_dentro_de_um_array_multidimensional()
    {
        // Simulando um request (como um formulário) que veio cheio de dados
        $dadosEntrada = [
            'nome' => 'Maria Silva',
            'comentario' => '<script>alert("Roubando cookies")</script>',
            'detalhes' => [
                'idade' => 25,
                'site_pessoal' => 'javascript:alert(1)'
            ]
        ];

        $resultado = $this->xssService->cleanArray($dadosEntrada);

        // Verificando a regra de negócio exata que você escreveu no seu método cleanArray:
        // $value = $this->antiXSS->isXssFound() ? '' : $value;
        
        // O que era seguro, deve continuar igual:
        $this->assertEquals('Maria Silva', $resultado['nome']);
        $this->assertEquals(25, $resultado['detalhes']['idade']);
        
        // O que tinha XSS deve ter virado uma string vazia (''), conforme a sua regra:
        $this->assertEquals('', $resultado['comentario']); 
        $this->assertEquals('', $resultado['detalhes']['site_pessoal']); 
    }
    
    public function test_deve_aceitar_valor_nulo_sem_quebrar_a_aplicacao()
    {
        $resultado = $this->xssService->clean(null);
        
        // Garante que passar null não causa um erro fatal e retorna um valor vazio seguro
        $this->assertEmpty($resultado);
    }
}