<?php

namespace App\Services;

use App\Interfaces\PdfExporterInterface;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adapter para a biblioteca DomPDF.
 * Implementa a interface PdfExporterInterface usando a facade do DomPDF.
 */
class DomPdfService implements PdfExporterInterface
{
    /**
     * {@inheritdoc}
     */
    public function downloadFromView(string $viewName, array $data, string $fileName, string $paperSize = 'a4', string $orientation = 'landscape'): Response
    {
        try {
            $pdf = PDF::loadView($viewName, $data);
            $pdf->setPaper($paperSize, $orientation);

            return $pdf->download($fileName);
        } catch (\Throwable $th) {
            throw new Exception('Erro ao gerar PDF: ' . $th->getMessage(). ' - ' . $th->getLine());
        }
    }
}
