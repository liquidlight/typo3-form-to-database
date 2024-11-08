<?php

declare(strict_types=1);

/**
 * This file is part of the "form_to_database" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Lavitto\FormToDatabase\Utility;

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Domain\Configuration\ConfigurationService;
use TYPO3\CMS\Form\Domain\Configuration\Exception\PrototypeNotFoundException;
use TYPO3\CMS\Form\Domain\Exception\RenderingException;
use TYPO3\CMS\Form\Domain\Exception\TypeDefinitionNotFoundException;
use TYPO3\CMS\Form\Domain\Exception\TypeDefinitionNotValidException;
use TYPO3\CMS\Form\Domain\Factory\ArrayFormFactory;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\FormElements\Page;
use TYPO3\CMS\Form\Domain\Model\Renderable\CompositeRenderableInterface;
use TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface;
use TYPO3\CMS\Form\Exception;

/**
 * Class FormDefinitionUtility
 */
class FormDefinitionUtility
{
    public const fieldAttributeFilterKeys = ['identifier', 'label', 'type'];

    /**
     * @param array<array-key, mixed> $formDefinition
     * @throws Exception
     * @throws PrototypeNotFoundException
     * @throws TypeDefinitionNotFoundException
     * @throws TypeDefinitionNotValidException
     */
    public static function addFieldStateIfDoesNotExist(
        array &$formDefinition,
        bool $force = false
    ): void {
        $fieldState = $formDefinition['renderingOptions']['fieldState'] ?? [];

        // If no state exists - create state from current fields
        if (empty($fieldState) || $force === true) {
            $newFieldState = self::addFieldsToStateFromFormDefinition(
                self::convertFormDefinitionToObject($formDefinition),
                $fieldState
            );

            //Mark all fields in state as not deleted
            $newFieldState = array_map(function ($field) {
                if (!isset($field['renderingOptions']['deleted'])) {
                    $field['renderingOptions']['deleted'] = 0;
                }
                return $field;
            }, $newFieldState);

            // Clean up fieldState - remove if incomplete
            $newFieldState = array_filter($newFieldState, fn($field): bool => !self::isCompositeElement($field) &&
            count(array_intersect_key(array_flip(self::fieldAttributeFilterKeys), $field)) === count(self::fieldAttributeFilterKeys));

            $formDefinition['renderingOptions']['fieldState'] = $newFieldState;
        }
    }

    /**
     * @param FormDefinition $formDefinition
     * @param array<array-key, mixed> $fieldState
     * @return array<array-key, mixed>
     */
    protected static function addFieldsToStateFromFormDefinition(FormDefinition $formDefinition, array $fieldState = []): array
    {
        foreach ($formDefinition->getRenderablesRecursively() as $renderable) {
            if ($renderable instanceof CompositeRenderableInterface) {
                // Prevent composite elements within field state to avoid
                // duplication errors within form definition build
                continue;
            }
            self::addFieldToState($fieldState, $renderable);
        }
        return $fieldState;
    }

    /**
     * @param array<array-key, mixed> $fieldState
     */
    public static function addFieldToState(array &$fieldState, RenderableInterface $renderable): void
    {
        ArrayUtility::mergeRecursiveWithOverrule(
            $fieldState,
            [$renderable->getIdentifier() =>
                ['identifier' => $renderable->getIdentifier(),
                    'label' => $renderable->getLabel(),
                    'type' => $renderable->getType(),
                    'renderingOptions' => ['deleted' => 0],
                ]]
        );
    }

    /**
     * @param array<array-key, mixed> $formDefinition
     * @throws RenderingException
     */
    public static function convertFormDefinitionToObject(array $formDefinition): FormDefinition
    {

        /** @var ArrayFormFactory $arrayFormFactory */
        $arrayFormFactory = GeneralUtility::makeInstance(ArrayFormFactory::class);
        return $arrayFormFactory->build($formDefinition);
    }

    /**
     * @param array<array-key, mixed> $field
     * @return bool
     * @throws PrototypeNotFoundException
     * @throws TypeDefinitionNotFoundException
     * @throws TypeDefinitionNotValidException
     * @throws Exception
     */
    public static function isCompositeElement(array $field): bool
    {
        static $page;
        static $compositeRenderables = [];
        if (!isset($page)) {
            $prototypeConfiguration = GeneralUtility::makeInstance(ConfigurationService::class)
                ->getPrototypeConfiguration('standard');

            $formDef = GeneralUtility::makeInstance(
                FormDefinition::class,
                'fieldStageForm',
                $prototypeConfiguration,
                'Form'
            );

            $page = GeneralUtility::makeInstance(Page::class, 'fieldStatePage', 'Page');
            $page->setParentRenderable($formDef);
        }
        if (in_array($field['type'], ['SummaryPage', 'Page'])) {
            $compositeRenderables[$field['identifier']] = true;
        } elseif (!isset($compositeRenderables[$field['identifier']])) {
            $element = $page->createElement($field['identifier'], $field['type']);
            $compositeRenderables[$field['identifier']] = $element instanceof CompositeRenderableInterface;
        }
        return $compositeRenderables[$field['identifier']];
    }
}
