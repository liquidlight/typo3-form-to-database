<?php
/**
 * This file is part of the "form_to_database" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Lavitto\FormToDatabase\Utility;

use DateTimeZone;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Form\Domain\Model\FormElements\FormElementInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Class FormValueUtility
 */
class FormValueUtility implements SingletonInterface
{
    /**
     * Output type definition "html"
     */
    public const OUTPUT_TYPE_HTML = 'html';

    /**
     * Output type definition "csv"
     */
    public const OUTPUT_TYPE_CSV = 'csv';

    /**
     * Maximum string length for html output
     */
    protected const HTML_CROP_MAX_CHARS = 30;

    /**
     * Characters to append, if a text is cropped
     */
    protected const HTML_CROP_APPEND = '...';

    /**
     * Respect word boundaries on crop
     */
    protected const HTML_CROP_RESPECT_WORD_BOUNDARIES = true;

    /**
     * Checks if a string can be interpreted as a combined file identifier, ex. 1:/user_upload/test.jpg
     */
    protected const COMBINED_FILE_IDENTIFIER_REGEX = '^([1-9]{1}[0-9]*)(\:)(.*)$';

    /**
     * @var ExtConfUtility
     */
    protected static $extConfUtility;

    /**
     * Converts a form value from database value to a human readable output
     *
     * @param FormElementInterface $element
     * @param $value
     * @param string $outputType
     * @param bool $cropText
     * @return string
     */
    public static function convertFormValue(
        FormElementInterface $element,
        $value,
        string $outputType = self::OUTPUT_TYPE_HTML,
        bool $cropText = false
    ): string {
        switch ($element->getType()) {
            case 'Date':
            case 'DatePicker':
                if (is_array($value) && !empty($value)) {
                    $value = self::getDateValue($element, $value);
                }
                break;
            case 'FileUpload':
            case 'ImageUpload':
                if (is_string($value) && preg_match('/' . self::COMBINED_FILE_IDENTIFIER_REGEX . '/', $value)) {
                    $fileLink = self::getFileLink($value);
                    if ($fileLink !== '') {
                        $label = PathUtility::basename($value);
                        if ($outputType === self::OUTPUT_TYPE_HTML) {
                            if ($cropText === true) {
                                $label = self::cropText($label);
                            }
                            $value = '<a href="' . $fileLink . '" target="_blank" title="' . $value . '">' . $label . '</a>';
                        } elseif (self::getExtConfUtility()->getConfig('csvOnlyFilenameOfUploadFields') === true) {
                            $value = $label;
                        } else {
                            $value = $fileLink;
                        }
                    }
                } else {
                    $value = '';
                }
                break;
            case 'Textarea':
                if (is_string($value)) {
                    $value = htmlspecialchars((string)$value);
                    if ($outputType === self::OUTPUT_TYPE_HTML) {
                        if ($cropText === true) {
                            $value = self::cropText($value);
                        } else {
                            $value = nl2br(trim($value));
                        }
                    }
                } else {
                    $value = '';
                }
                break;
            default:
                if (is_string($value)) {
                    $value = htmlspecialchars((string)$value);
                    if ($outputType === self::OUTPUT_TYPE_HTML && $cropText === true) {
                        $value = self::cropText($value);
                    }
                } elseif (is_array($value)) {
                    $value = implode(', ', $value);
                } else {
                    $value = '';
                }
                break;
        }
        $value = (string)$value;
        if ($value === '' && $outputType === self::OUTPUT_TYPE_HTML) {
            $value = '&nbsp;';
        }
        return $value;
    }

    /**
     * Returns an absolute link to a file by the combined identifier
     *
     * @param string $combinedFileIdentifier
     * @return string
     */
    protected static function getFileLink(string $combinedFileIdentifier): string
    {
        $fileLink = '';
        /** @var ResourceFactory $resourceFactory */
        $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
        try {
            $fileObject = $resourceFactory->getFileObjectFromCombinedIdentifier($combinedFileIdentifier);
        } catch (\Exception $e) {
            $fileObject = null;
        }
        if ($fileObject instanceof FileInterface) {
            if ($fileObject->getStorage()->isPublic() === true) {
                $publicUrl = PathUtility::getAbsoluteWebPath($fileObject->getPublicUrl());
                $fileLink = GeneralUtility::locationHeaderUrl($publicUrl);
            } else {
                $fileLink = $fileObject->getPublicUrl();
            }
        }
        return $fileLink;
    }

    /**
     * Converts a date(time) value array to a human readable string
     *
     * @param FormElementInterface $element
     * @param array $dateValue
     * @return string
     */
    protected static function getDateValue(FormElementInterface $element, array $dateValue): string
    {
        $dateTime = null;
        try {
            $dateTime = new \DateTime($dateValue['date'], self::getValidTimezone($dateValue['timezone']));
        } catch (\Exception $e) {
        }
        $properties = $element->getProperties();
        $format = $properties['dateFormat'] ?? $properties['displayFormat'] ?? self::getDateFormat();
        return $dateTime instanceof \DateTime ? $dateTime->format($format) : '';
    }

    /**
     * Crops text
     *
     * @param string $text
     * @return string
     * @noinspection PhpInternalEntityUsedInspection
     */
    protected static function cropText(string $text): string
    {
        /** @var ContentObjectRenderer $contentObject */
        $contentObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        return $contentObject->crop(
            $text,
            self::HTML_CROP_MAX_CHARS . '|' . self::HTML_CROP_APPEND . '|' . self::HTML_CROP_RESPECT_WORD_BOUNDARIES
        );
    }

    /**
     * Returns the date format, configured in TYPO3_CONF_VARS with fallback-option
     *
     * @return string
     */
    public static function getDateFormat(): string
    {
        return $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] ?? 'd-m-y';
    }

    /**
     * Returns the time format, configured in TYPO3_CONF_VARS with fallback-option
     *
     * @return string
     */
    public static function getTimeFormat(): string
    {
        return $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'] ?? 'H:i';
    }

    /**
     * Returns a valid DateTimeZone with fallback function TYPO3_CONF_VARS > default_timezone > UTC
     *
     * @param string $timeZone
     * @return \DateTimeZone
     */
    public static function getValidTimezone(string $timeZone): \DateTimeZone
    {
        $timeZoneIdentifiers = timezone_identifiers_list();
        if (in_array($timeZone, $timeZoneIdentifiers, true) === true) {
            $validTimeZone = $timeZone;
        } elseif (in_array(date_default_timezone_get(), $timeZoneIdentifiers, true) === true) {
            $validTimeZone = date_default_timezone_get();
        } else {
            $validTimeZone = \DateTimeZone::UTC;
        }
        return new \DateTimeZone($validTimeZone);
    }

    /**
     * @return ExtConfUtility
     */
    protected static function getExtConfUtility(): ExtConfUtility
    {
        if (self::$extConfUtility === null) {
            self::$extConfUtility = GeneralUtility::makeInstance(ExtConfUtility::class);
        }
        return self::$extConfUtility;
    }
}
