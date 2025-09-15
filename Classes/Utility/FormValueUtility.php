<?php

declare(strict_types=1);

/**
 * This file is part of the "form_to_database" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Lavitto\FormToDatabase\Utility;

use DateTimeZone;
use Lavitto\FormToDatabase\Exception\FileNotFoundException;
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

    protected static ?ExtConfUtility $extConfUtility = null;

    /**
     * Converts a form value from database value to a human readable output
     */
    public static function convertFormValue(
        FormElementInterface $element,
        mixed $value,
        string $outputType = self::OUTPUT_TYPE_HTML,
        bool $cropText = false
    ): string {
        switch ($element->getType()) {
            case 'Date':
            case 'DatePicker':
                if (is_array($value) && array_key_exists('date', $value) && array_key_exists('timezone', $value)) {
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
                    if (
                        $outputType === self::OUTPUT_TYPE_CSV
                        && !self::getExtConfUtility()->getConfig('csvHtmlSpecialChars')
                    ) {
                        $value = htmlspecialchars_decode((string)$value, ENT_QUOTES);
                    } else {
                        $value = htmlspecialchars((string)$value);
                    }
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
                    if (
                        $outputType === self::OUTPUT_TYPE_CSV
                        && !self::getExtConfUtility()->getConfig('csvHtmlSpecialChars')
                    ) {
                        $value = htmlspecialchars_decode((string)$value, ENT_QUOTES);
                    } else {
                        $value = htmlspecialchars((string)$value);
                    }
                    if ($outputType === self::OUTPUT_TYPE_HTML && $cropText === true) {
                        $value = self::cropText($value);
                    }
                } elseif (is_array($value)) {
                    $value = implode(', ', array_map('htmlspecialchars', $value));
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
     * @throws FileNotFoundException
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
            if ($fileObject->getStorage()->isPublic() && !$fileObject->isDeleted()) {
                $internalPublicUrl = $fileObject->getPublicUrl();
                if ($internalPublicUrl === null) {
                    throw new FileNotFoundException(
                        sprintf('The requested file "%s" was not found. It is either deleted or missing.', $fileObject->getIdentifier()),
                        1730896217433
                    );
                }
                $publicUrl = PathUtility::getAbsoluteWebPath($internalPublicUrl);
                $fileLink = GeneralUtility::locationHeaderUrl($publicUrl);
            } else {
                $fileLink = $fileObject->getPublicUrl();
                if ($fileLink === null) {
                    throw new FileNotFoundException(
                        sprintf('The requested file "%s" was not found. It is either deleted or missing.', $fileObject->getIdentifier()),
                        1730896340759
                    );
                }
            }
        }
        return $fileLink;
    }

    /**
     * Converts a date(time) value array to a human readable string
     *
     * @param FormElementInterface $element
     * @param array{
     *     date: string,
     *     timezone: string
     * } $dateValue
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
     * @return \DateTimeZone
     * @throws \DateInvalidTimeZoneException
     */
    public static function getValidTimezone(string $timeZone): \DateTimeZone
    {
        $timeZoneIdentifiers = timezone_identifiers_list();
        if (in_array($timeZone, $timeZoneIdentifiers, true)) {
            $validTimeZone = $timeZone;
        } elseif (in_array(date_default_timezone_get(), $timeZoneIdentifiers, true)) {
            $validTimeZone = date_default_timezone_get();
        } else {
            // changed from \DateTimeZone::UTC to hard-coded string 'UTC',
            // as constructor expects string, but constant is integer
            $validTimeZone = 'UTC';
        }
        return new \DateTimeZone($validTimeZone);
    }

    /**
     * @return ExtConfUtility
     */
    protected static function getExtConfUtility(): ExtConfUtility
    {
        self::$extConfUtility ??= GeneralUtility::makeInstance(ExtConfUtility::class);
        return self::$extConfUtility;
    }

    /**
     * Finds matching form values by direct match or when prefixed by a container
     * @param array<string, mixed> $results
     * @param string $identifier
     * @return array<mixed>
     */
    public static function findValuesByIdentifier(array $results, string $identifier): array
    {
        $values = [];

        // Try direct match
        if (array_key_exists($identifier, $results) && $results[$identifier] !== null) {
            $values[] = $results[$identifier];
        }

        // Try suffix match
        foreach ($results as $key => $value) {
            if ($value !== null && preg_match('/\.' . preg_quote($identifier, '/') . '$/', $key)) {
                $values[] = $value;
            }
        }

        return $values;
    }

    /**
     * Finds matching form values and converts them to a string
     * @param FormElementInterface $element
     * @param array<string, mixed> $results
     * @param string $valueIdentifier
     * @param bool $crop
     * @return string
     */
    public static function prepareFormValues(FormElementInterface $element, array $results, string $valueIdentifier, bool $crop = false): string
    {
        $values = self::findValuesByIdentifier($results, $valueIdentifier);

        if (count($values) > 1) {
            return self::prepareMultipleValues($element, $values, $crop);
        }

        return self::convertFormValue($element, reset($values) ?? null, self::OUTPUT_TYPE_HTML, $crop);
    }

    /**
     * Formats multiple value matches (such as repeatables) as a single string with numbered lines
     * @param FormElementInterface $element
     * @param array<mixed> $values
     * @param bool $crop
     * @return string
    */
    public static function prepareMultipleValues(FormElementInterface $element, array $values, bool $crop): string
    {
        // Convert each value to appropriate string format
        $convertedValues = array_map(function ($value) use ($element, $crop) {
            return self::convertFormValue($element, $value, self::OUTPUT_TYPE_HTML, $crop);
        }, $values);

        // Reorder to original form submission order
        $orderedValues = array_reverse($convertedValues);

        // Prepend each value with a line number
        $numberedValues = array_map(
            fn($line, $index) => ($index + 1) . ') ' . $line,
            $orderedValues,
            array_keys($orderedValues)
        );

        return implode('<br>', $numberedValues);
    }

}
