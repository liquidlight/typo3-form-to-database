<?php

namespace Lavitto\FormToDatabase\Utility;

use Mpdf\Mpdf;
use Mpdf\HTMLParserMode;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PdfUtility
{
    public function __construct(
        protected $settings = []
    ) {

    }

    /**
     * Generate a PDF and return the filename
     *
     * Borrowed and adapted from EXT:web2pdf
     */
    public function generatePdf($html, $fileName = '')
    {
        // Set default options
        $config = [
            'default_font_size' => '12',
            'format' => 'A4',
            'orientation' => 'P',
            'margin_left' => '15',
            'margin_right' => '15',
            'margin_bottom' => '15',
            'margin_top' => '15',
            'tempDir' => Environment::getVarPath() . '/form_to_database'
        ];

        // Merge with TypoScript options
        $config = array_merge($config, $this->settings['config'] ?? []);

        // Generate PDF class
        $pdf = new Mpdf($config);

        $pdf->SetMargins($config['margin_left'], $config['margin_right'], $config['margin_top']);

        if (
            $this->settings['stylesheet']['media'] ?? false &&
            in_array($this->settings['stylesheet']['media'], ['print', 'screen'])
        ) {
            $pdf->CSSselectMedia = $this->settings['stylesheet']['media'];
        }

        $fileName = $fileName . '.pdf';
        $filePath = Environment::getVarPath() . '/form_to_database/' . $fileName;

        if($this->settings['stylesheet']['link'] ?? false) {
            $css = GeneralUtility::getFileAbsFileName($this->settings['stylesheet']['link']);
            if(is_file($css)) {
                $pdf->WriteHTML(file_get_contents($css), HTMLParserMode::HEADER_CSS);
            }
        }

        if ($this->settings['letterheads']['header'] ?? false) {
            $pdf->SetHTMLHeader($this->settings['letterheads']['header']);
        }
        if ($this->settings['letterheads']['footer'] ?? false) {
            $pdf->SetHTMLFooter($this->settings['letterheads']['footer']);
        }

        $pdf->WriteHTML($html, HTMLParserMode::HTML_BODY);
        $pdf->Output($filePath, 'F');

        return $filePath;
    }
}
