<?php /** @noinspection PhpInternalEntityUsedInspection */

/**
 * This file is part of the "form_to_database" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace LiquidLight\FormToDatabase\Utility;

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Form\Domain\Configuration\ConfigurationService;
use TYPO3\CMS\Form\Domain\Configuration\Exception\PrototypeNotFoundException;
use TYPO3\CMS\Form\Domain\Exception\TypeDefinitionNotFoundException;
use TYPO3\CMS\Form\Domain\Exception\TypeDefinitionNotValidException;
use TYPO3\CMS\Form\Domain\Factory\ArrayFormFactory;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\FormElements\Page;
use TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface;
use TYPO3\CMS\Form\Exception;

/**
 * Class FormDefinitionUtility
 *
 * @package LiquidLight\FormToDatabase\Utility
 */
class FormDefinitionUtility
{

    const fieldAttributeFilterKeys = ['identifier', 'label', 'type'];

    /**
     * @param array $formDefinition
     * @param bool $force
     */
    public static function addFieldStateIfDoesNotExist(
        array &$formDefinition,
        bool  $force = false
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
                if(!isset($field['renderingOptions']['deleted'])) {
                    $field['renderingOptions']['deleted'] = 0;
                }
                return $field;
            }, $newFieldState);

            // Clean up fieldState - remove if incomplete
            $newFieldState = array_filter($newFieldState, function($field) {
                return
                    !self::isCompositeElement($field) &&
                    count(array_intersect_key(array_flip(self::fieldAttributeFilterKeys), $field)) === count(self::fieldAttributeFilterKeys);
            });


            $formDefinition['renderingOptions']['fieldState'] = $newFieldState;
        }
    }

    /**
     * @param FormDefinition $formDefinition
     * @param array $fieldState
     * @return array
     */
    protected static function addFieldsToStateFromFormDefinition(FormDefinition $formDefinition, array $fieldState = []): array
    {
        foreach ($formDefinition->getRenderablesRecursively() as $renderable) {
            if ($renderable instanceof \TYPO3\CMS\Form\Domain\Model\Renderable\CompositeRenderableInterface) {
                // Prevent composite elements within field state to avoid
                // duplication errors within form definition build
                continue;
            }
            self::addFieldToState($fieldState, $renderable);
        }
        return $fieldState;
    }

    /**
     * @param $fieldState
     * @param RenderableInterface $renderable
     */
    public static function addFieldToState(&$fieldState, RenderableInterface $renderable): void
    {
        ArrayUtility::mergeRecursiveWithOverrule($fieldState,
            [$renderable->getIdentifier() =>
                ['identifier' => $renderable->getIdentifier(),
                    'label' => $renderable->getLabel(),
                    'type' => $renderable->getType(),
                    'renderingOptions' => ['deleted' => 0]
                ]]);
    }

    /**
     * @param array $formDefinition
     * @return FormDefinition
     */
    public static function convertFormDefinitionToObject(array $formDefinition): FormDefinition
    {

        /** @var ArrayFormFactory $arrayFormFactory */
        $arrayFormFactory = GeneralUtility::makeInstance(ArrayFormFactory::class);
        return $arrayFormFactory->build($formDefinition);
    }


    /**
     * @param array $field
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
        if(!isset($page)) {
            $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
            $prototypeConfiguration = $objectManager->get(ConfigurationService::class)
                ->getPrototypeConfiguration('standard');

            $formDef = $objectManager->get(
                FormDefinition::class,
                'fieldStageForm',
                $prototypeConfiguration,
                'Form'
            );

            $page = $objectManager->get(Page::class, 'fieldStatePage', 'Page');
            $page->setParentRenderable($formDef);
        }
        if(in_array($field['type'], ['SummaryPage','Page'])) {
            $compositeRenderables[$field['identifier']] = true;
        } elseif (!isset($compositeRenderables[$field['identifier']])) {
            $element = $page->createElement($field['identifier'], $field['type']);
            $compositeRenderables[$field['identifier']] = $element instanceof \TYPO3\CMS\Form\Domain\Model\Renderable\CompositeRenderableInterface;
        }
        return $compositeRenderables[$field['identifier']];
    }
}
