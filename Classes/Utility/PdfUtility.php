<?php

namespace Lavitto\FormToDatabase\Utility;

use Mpdf\Mpdf;
use TYPO3\CMS\Core\Core\Environment;

class PdfUtility
{
	/**
	 * Generate a PDF and return the filename
	 *
	 * Borrowed and adapted from EXT:web2pdf
	 */
    public function generatePdf($html, $fileName = '')
    {
        // Get options from TypoScript
        $pageFormat = 'A4';
        $pageOrientation = 'P';
        $leftMargin = '15';
        $rightMargin = '15';
        $bottomMargin = '15';
        $topMargin = '15';
        $styleSheet = 'print';

        $pdf = new Mpdf([
            'format' => $pageFormat,
            'default_font_size' => 12,
            'margin_left' => $leftMargin,
            'margin_right' => $rightMargin,
            'margin_top' => $topMargin,
            'margin_bottom' => $bottomMargin,
            'orientation' => $pageOrientation,
            'tempDir' => Environment::getVarPath() . '/form_to_database'
        ]);

        $pdf->SetMargins($leftMargin, $rightMargin, $topMargin);
        if ($styleSheet === 'print' || $styleSheet === 'screen') {
            $pdf->CSSselectMedia = $styleSheet;
        }

        $fileName = $fileName . '.pdf';
        $filePath = Environment::getVarPath() . '/form_to_database/' . $fileName;

        $pdf->WriteHTML($html);
        $pdf->Output($filePath, 'F');

        return $filePath;
    }
}
