<?php

namespace Tests\Feature\Services;

use Tests\TestCase;
use App\Services\DomPdfService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response; 
use Exception;
use Mockery;

class DomPdfServiceTest extends TestCase
{
    protected DomPdfService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DomPdfService();
    }

    public function test_deve_gerar_o_download_do_pdf_com_os_parametros_corretos()
    {
        // Mock tipado com a classe exata que o PHP 8 exige
        $pdfMock = Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        
        $pdfMock->shouldReceive('setPaper')
                ->with('a4', 'landscape')
                ->once()
                ->andReturnSelf(); 

        $pdfMock->shouldReceive('download')
                ->with('relatorio.pdf')
                ->once()
                ->andReturn(new Response('conteudo-falso-do-pdf'));

        Pdf::shouldReceive('loadView')
            ->with('minha-view-falsa', ['dados' => 123])
            ->once()
            ->andReturn($pdfMock);

        $response = $this->service->downloadFromView('minha-view-falsa', ['dados' => 123], 'relatorio.pdf');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('conteudo-falso-do-pdf', $response->getContent());
    }
    
    public function test_deve_lancar_excecao_se_o_dompdf_falhar()
    {
        Pdf::shouldReceive('loadView')->andThrow(new Exception('Falha interna da biblioteca'));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Erro ao gerar PDF: Falha interna da biblioteca');

        $this->service->downloadFromView('qualquer-view', [], 'arquivo.pdf');
    }
}