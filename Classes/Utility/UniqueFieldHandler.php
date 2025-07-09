<?php /** @noinspection PhpInternalEntityUsedInspection */

/**
 * This file is part of the "form_to_database" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Lavitto\FormToDatabase\Utility;

use TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface;
use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManager;

/**
 * Class FormDefinitionUtility
 *
 * @package Lavitto\FormToDatabase\Utility
 */
class UniqueFieldHandler
{

    protected array $existingFieldsBeforeSave = [];
    protected array $activeFields = [];

    /**
     * @var array
     */
    protected array $fieldTypesNextIdentifier = [];

    /**
     * @var FormPersistenceManager
     */
    protected FormPersistenceManager $formPersistenceManager;

    /**
     * @param FormPersistenceManager $formPersistenceManager
     */
    public function __construct(FormPersistenceManager $formPersistenceManager)
    {
        $this->formPersistenceManager = $formPersistenceManager;
    }

    /**
     * Makes sure that field identifiers are unique (identifiers of deleted fields are not reused)
     * Field state is saved to keep track of old fields
     *
     * @param $formPersistenceIdentifierBeforeSave
     * @param $formDefinition
     * @return mixed
     */
    public function updateNewFields($formPersistenceIdentifierBeforeSave, $formDefinition)
    {
        $fieldCount = 0;
        $this->setExistingFieldsBeforeSave($formPersistenceIdentifierBeforeSave);
        $formStateDidAlreadyExist = !!($formDefinition['renderingOptions']['fieldState'] ?? false);
        FormDefinitionUtility::addFieldStateIfDoesNotExist($formDefinition, true);

        // Only process if formState already existed - else no changes should be considered
        if($formStateDidAlreadyExist) {
            //Make map of next identifier for each field type
            $this->makeNextIdentifiersMap($formDefinition['renderingOptions']['fieldState']);

            foreach (FormDefinitionUtility::convertFormDefinitionToObject($formDefinition)->getRenderablesRecursively() as $renderable) {
                if($renderable instanceof \TYPO3\CMS\Form\Domain\Model\Renderable\CompositeRenderableInterface) {
                    continue;
                }
                $fieldCount++;
                if (
                    !in_array($renderable->getIdentifier(), $this->existingFieldsBeforeSave)
                    ||
                    ($formDefinition['renderingOptions']['fieldState'][$renderable->getIdentifier()]['renderingOptions']['deleted'] ?? 0) === 1
                ) {
                    //Existing field - update state
                    $this->updateNewFieldWithNextIdentifier($formDefinition['renderables'], $renderable);
                }
                FormDefinitionUtility::addFieldToState($formDefinition['renderingOptions']['fieldState'], $renderable);
                $this->activeFields[] = $renderable->getIdentifier();
            }
            $this->updateStateDeletedState($formDefinition);
        }
        return $formDefinition;
    }

    /**
     * @param $fieldState
     */
    protected function makeNextIdentifiersMap($fieldState): void
    {
        foreach ($fieldState as $identifier => &$field) {
            $identifierParts = explode('-', $field['identifier']);
            $identifierText = $identifierParts[0];
            $identifierNumber = $identifierParts[1] ?? '0';

            if (!isset($this->fieldTypesNextIdentifier[$field['type']])) {
                $this->fieldTypesNextIdentifier[$field['type']] = [
                    'text' => $identifierText,
                    'number' => $identifierNumber
                ];
            } else {
                $this->fieldTypesNextIdentifier[$field['type']] = [
                    'text' => $identifierText,
                    'number' => max($this->fieldTypesNextIdentifier[$field['type']]['number'], $identifierNumber)
                ];
            }
        }
        unset($field);
        array_walk($this->fieldTypesNextIdentifier, static function (&$val) {
            $val['number']++;
        });
    }

    /**
     * @param array $renderables
     * @param RenderableInterface $newFieldObject
     * @return true
     */
    protected function updateNewFieldWithNextIdentifier(array &$renderables, RenderableInterface &$newFieldObject): bool
    {
        if (!empty($renderables)) {
            foreach ($renderables as &$renderable) {
                if(isset($renderable['renderables'])) {
                    if($this->updateNewFieldWithNextIdentifier($renderable['renderables'], $newFieldObject)) return true;
                } else {
                    if($renderable['identifier'] == $newFieldObject->getIdentifier()) {
                        if (isset($this->fieldTypesNextIdentifier[$newFieldObject->getType()])) {
                            $renderable['identifier'] = $this->fieldTypesNextIdentifier[$newFieldObject->getType()]['text'] . '-' . $this->fieldTypesNextIdentifier[$newFieldObject->getType()]['number'];
                            $this->fieldTypesNextIdentifier[$newFieldObject->getType()]['number']++;
                            $newFieldObject->setIdentifier($renderable['identifier']);
                        }
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * @param $formDefinition
     */
    protected function updateStateDeletedState(&$formDefinition): void
    {
        $formDefinition['renderingOptions']['fieldState'] = array_map(function ($field) {
            $field['renderingOptions']['deleted'] = in_array($field['identifier'], $this->activeFields) ? 0 : 1;
            return $field;
        }, $formDefinition['renderingOptions']['fieldState']);
    }

    /**
     * @param string $formPersistenceIdentifier
     * @return void
     */
    protected function setExistingFieldsBeforeSave(string $formPersistenceIdentifier) {
        $formDefinitionBeforeSave = $this->formPersistenceManager->load($formPersistenceIdentifier);
        $renderables = FormDefinitionUtility::convertFormDefinitionToObject($formDefinitionBeforeSave)->getRenderablesRecursively();
        foreach ($renderables as $renderable) {
            if(!$renderable instanceof \TYPO3\CMS\Form\Domain\Model\Renderable\CompositeRenderableInterface) {
                $this->existingFieldsBeforeSave[] = $renderable->getIdentifier();
            }
        }
    }
}
