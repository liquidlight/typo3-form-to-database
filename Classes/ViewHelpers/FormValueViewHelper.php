<?php
/**
 * This file is part of the "form_to_database" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace LiquidLight\FormToDatabase\ViewHelpers;

use LiquidLight\FormToDatabase\Utility\FormValueUtility;
use TYPO3\CMS\Form\Domain\Model\FormElements\FormElementInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Class FormValueViewHelper
 *
 * @package LiquidLight\FormToDatabase\ViewHelpers
 */
class FormValueViewHelper extends AbstractViewHelper
{

    /**
     * Do not escape the output
     *
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * Initialize the arguments
     */
    public function initializeArguments(): void
    {
        $this->registerArgument('element', FormElementInterface::class, 'the form element', true);
        $this->registerArgument('value', 'string', 'the identifier', true);
        $this->registerArgument('results', 'array', 'the form results', true);
        $this->registerArgument('crop', 'bool', 'should the result be cropped', false, false);
    }

    /**
     * Converts and returns the value as a string
     *
     * @return string
     * @see FormValueUtility::convertFormValue()
     */
    public function render(): string
    {
        return FormValueUtility::convertFormValue($this->arguments['element'], $this->arguments['results'][$this->arguments['value']] ?? '',
            FormValueUtility::OUTPUT_TYPE_HTML, $this->arguments['crop'] === true);
    }
}
