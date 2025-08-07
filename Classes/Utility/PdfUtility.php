<?php

declare(strict_types=1);

namespace Lavitto\FormToDatabase\Utility;

use Lavitto\FormToDatabase\Exception\FileWriteNotPossibleException;
use Lavitto\FormToDatabase\Exception\MpdfNotLoadedException;
use Lavitto\FormToDatabase\Exception\ResourceIsNotCreatableException;
use Mpdf\HTMLParserMode;
use Mpdf\Mpdf;
use Mpdf\MpdfException;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class PdfUtility
{
    /**
     * @var array{
     *      config?: array{
     *         mode?: string,
     *         format?: string,
     *         default_font_size?: int,
     *         default_font?: string,
     *         margin_left?: int,
     *         margin_right?: int,
     *         margin_top?: int,
     *         margin_bottom?: int,
     *         margin_header?: int,
     *         margin_footer?: int,
     *         orientation?: string,
     *      },
     *      stylesheet?: array{
     *          media?: string,
     *          link?: string,
     *      },
     *      letterheads?: array{
     *          header?: string,
     *          footer?: string
     *      }
     *  }
     */
    private array $settings;

    /**
     * @param array{
     *     config?: array{
     *        mode?: string,
     *        format?: string,
     *        default_font_size?: int,
     *        default_font?: string,
     *        margin_left?: int,
     *        margin_right?: int,
     *        margin_top?: int,
     *        margin_bottom?: int,
     *        margin_header?: int,
     *        margin_footer?: int,
     *        orientation?: string,
     *     },
     *     stylesheet?: array{
     *         media?: string,
     *         link?: string,
     *     },
     *     letterheads?: array{
     *         header?: string,
     *         footer?: string
     *     }
     * } $settings
     */
    public function __construct(array $settings = [])
    {
        $this->settings = $settings;
        $this->settings['config'] ??= [];
        $this->settings['stylesheet'] ??= [];
        $this->settings['letterheads'] ??= [];
    }

    /**
     * Generate a PDF and return the filename
     *
     * Borrowed and adapted from EXT:web2pdf
     *
     * @return array{fileResource: resource, fileLength: int}
     *
     * @throws MpdfException
     * @throws ResourceIsNotCreatableException
     * @throws FileWriteNotPossibleException
     * @throws MpdfNotLoadedException
     */
    public function generatePdf(string $html): array
    {
        // If mPDF isn't installed
        if (!class_exists(Mpdf::class)) {
            throw new MpdfNotLoadedException(
                'The package mpdf is not installed',
                1731956735420
            );
        }

        // Set default options
        $config = [
            'default_font_size' => '12',
            'format' => 'A4',
            'orientation' => 'P',
            'margin_left' => '15',
            'margin_right' => '15',
            'margin_bottom' => '15',
            'margin_top' => '15',
            'tempDir' => Environment::getVarPath() . '/form_to_database',
        ];

        // Merge with TypoScript options
        $config = array_merge($config, $this->settings['config'] ?? []);

        // Generate PDF class
        $pdf = new Mpdf($config);

        $pdf->SetMargins($config['margin_left'], $config['margin_right'], $config['margin_top']);

        if (
            in_array(($this->settings['stylesheet']['media'] ?? []), ['print', 'screen'], true)
        ) {
            $pdf->CSSselectMedia = $this->settings['stylesheet']['media'];
        }

        if ($this->settings['stylesheet']['link'] ?? false) {
            $css = GeneralUtility::getFileAbsFileName($this->settings['stylesheet']['link']);
            if (is_file($css)) {
                $cssContent = file_get_contents($css);
                if ($cssContent !== false) {
                    $pdf->WriteHTML($cssContent, HTMLParserMode::HEADER_CSS);
                }
            }
        }

        if ($this->settings['letterheads']['header'] ?? false) {
            $pdf->SetHTMLHeader($this->settings['letterheads']['header']);
        }
        if ($this->settings['letterheads']['footer'] ?? false) {
            $pdf->SetHTMLFooter($this->settings['letterheads']['footer']);
        }

        $pdf->WriteHTML($html, HTMLParserMode::HTML_BODY);
        $pdfData = $pdf->OutputBinaryData();

        $pdfFile = fopen('php://memory', 'r+');
        if ($pdfFile === false) {
            throw new ResourceIsNotCreatableException(
                'Error while creating resource for PDF export',
                1731956515640
            );
        }

        $fileLength = fwrite($pdfFile, $pdfData);
        if ($fileLength === false) {
            throw new FileWriteNotPossibleException(
                'The PDF file could not get written',
                1731956602792
            );
        }

        return ['fileResource' => $pdfFile, 'fileLength' => $fileLength];
    }
}
