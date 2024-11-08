<?php

declare(strict_types=1);

/**
 * This file is part of the "form_to_database" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Lavitto\FormToDatabase\Domain\Finishers;

use Lavitto\FormToDatabase\Domain\Model\FormResult;
use Lavitto\FormToDatabase\Domain\Repository\FormResultRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Form\Domain\Finishers\AbstractFinisher;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\FormElements\FormElementInterface;

/**
 * Class FormToDatabaseFinisher
 */
class FormToDatabaseFinisher extends AbstractFinisher
{
    /**
     * Dont save this fields in database (also used in FromResultsController)
     */
    public const EXCLUDE_FIELDS = ['Honeypot', 'StaticText', 'ContentElement', 'GridRow', 'SummaryPage'];

    /**
     * The formDefinition
     *
     * @var FormDefinition
     */
    protected FormDefinition $formDefinition;

    /**
     * The FormResultRepository
     *
     * @var FormResultRepository
     */
    protected FormResultRepository $formResultRepository;

    /**
     * The ConfigurationManagerInterface
     *
     * @var ConfigurationManagerInterface
     */
    protected ConfigurationManagerInterface $configurationManager;

    /**
     * Injects the FormResultRepository
     *
     * @param FormResultRepository $formResultRepository
     */
    public function injectFormResultRepository(FormResultRepository $formResultRepository): void
    {
        $this->formResultRepository = $formResultRepository;
    }

    /**
     * Injects the ConfigurationManagerInterface
     *
     * @param ConfigurationManagerInterface $configurationManager
     */
    public function injectConfigurationManager(ConfigurationManagerInterface $configurationManager): void
    {
        $this->configurationManager = $configurationManager;
    }

    /**
     * getFormFieldValues
     *
     * Recursive method to get all form field values including nested ones
     *
     * @param array<string, mixed> $fields
     * @param array<array-key, mixed> $nestedIdentifier Array of levels nested - populated during recursion
     * @return array<string, mixed>
     */
    private function getFormFieldValues(array $fields, array $nestedIdentifier = []): array
    {
        $formValues = [];

        foreach ($fields as $fieldName => $fieldValue) {
            $newNestedIdentifier = $nestedIdentifier;

            // Are we a valid field or a repeatable container?
            $isValidField = !is_null($this->formDefinition->getElementByIdentifier($fieldName));

            if (is_array($fieldValue) && !$isValidField) {
                $newNestedIdentifier[] = $fieldName;
                $formValues = array_merge($this->getFormFieldValues($fieldValue, $newNestedIdentifier), $formValues);
            } else {
                if (count($nestedIdentifier)) {
                    $fieldNameIdentifier = array_merge($nestedIdentifier, [$fieldName]);
                    $fieldName = implode('.', $fieldNameIdentifier);
                }

                // Get the field with the new constructed name
                $fieldElement = $this->formDefinition->getElementByIdentifier($fieldName);

                if (
                    $fieldElement instanceof FormElementInterface &&
                    in_array(
                        $fieldElement->getType(),
                        self::EXCLUDE_FIELDS,
                        true
                    ) === false
                ) {
                    if ($fieldValue instanceof FileReference) {
                        $formValues[$fieldName] = $fieldValue->getOriginalResource()->getCombinedIdentifier();
                    } else {
                        $formValues[$fieldName] = $fieldValue;
                    }
                }
            }
        }

        return $formValues;
    }

    /**
     * Writes the form-result into the database, excludes Honeypot
     *
     * @throws IllegalObjectTypeException
     * @throws \JsonException
     */
    protected function executeInternal(): void
    {
        $this->formDefinition = $this->finisherContext->getFormRuntime()->getFormDefinition();
        $formPersistenceIdentifier = $this->formDefinition->getPersistenceIdentifier();

        $formValues = $this->getFormFieldValues($this->finisherContext->getFormValues());

        $formPluginUid = 0;
            $formIdentifier = $this->formDefinition->getIdentifier();

            $delimiter = (int)strrpos($this->formDefinition->getIdentifier(), '-');
if ($delimiter) {
                $formPluginUid = (int)substr($this->formDefinition->getIdentifier(), $delimiter + 1);
        $formIdentifier = substr($this->formDefinition->getIdentifier(), 0, $delimiter);}
        $formResult = GeneralUtility::makeInstance(FormResult::class);
        $formResult->setFormPersistenceIdentifier($formPersistenceIdentifier);
        $formResult->setSiteIdentifier($GLOBALS['TYPO3_REQUEST']->getAttribute('site')->getIdentifier());
        $formResult->setPid($GLOBALS['TSFE']->id);
        $formResult->setResultFromArray($formValues);
        $formResult->setFormPluginUid($formPluginUid);
        $formResult->setFormIdentifier($formIdentifier);

        $this->formResultRepository->add($formResult);
        $persistenceManager = GeneralUtility::makeInstance(PersistenceManager::class);
        $persistenceManager->persistAll();

        $this->finisherContext->getFinisherVariableProvider()->add(
            $this->shortFinisherIdentifier,
            'formToDatabase.formResult',
            $formResult
        );
    }
}
