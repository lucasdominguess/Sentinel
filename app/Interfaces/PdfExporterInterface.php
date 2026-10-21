<?php

namespace App\Interfaces;

/**
 * Interface para adaptadores de exportação de PDF.
 * Define um contrato para gerar e baixar PDFs a partir de uma view.
 */
interface PdfExporterInterface
{
    /**
     * Gera e inicia o download de um PDF a partir de uma view Blade.
     *
     * @param string $viewName O nome da view Blade a ser renderizada.
     * @param array $data Os dados a serem passados para a view.
     * @param string $fileName O nome do arquivo para o download.
     * @param string $paperSize O tamanho do papel (ex: 'a4').
     * @param string $orientation A orientação ('portrait' ou 'landscape').
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function downloadFromView(string $viewName, array $data, string $fileName, string $paperSize = 'a4', string $orientation = 'landscape');
}
