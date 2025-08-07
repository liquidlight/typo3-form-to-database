<?php

declare(strict_types=1);

/**
 * This file is part of the "form_to_database" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Lavitto\FormToDatabase\Utility;

use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Form\Domain\Configuration\Exception\PrototypeNotFoundException;
use TYPO3\CMS\Form\Domain\Exception\RenderingException;
use TYPO3\CMS\Form\Domain\Exception\TypeDefinitionNotFoundException;
use TYPO3\CMS\Form\Domain\Exception\TypeDefinitionNotValidException;
use TYPO3\CMS\Form\Domain\Model\Renderable\CompositeRenderableInterface;
use TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface;
use TYPO3\CMS\Form\Exception;
use TYPO3\CMS\Form\Mvc\Configuration\ConfigurationManagerInterface as ExtFormConfigurationManagerInterface;
use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManager;

/**
 * Class FormDefinitionUtility
 */
class UniqueFieldHandler
{
    /** @var array<array-key, mixed> */
    protected array $existingFieldStateBeforeSave = [];

    /** @var array<array-key, mixed> */
    protected array $activeFields = [];

    /** @var array<array-key, mixed> */
    protected array $fieldTypesNextIdentifier = [];

    public function __construct(
        protected readonly FormPersistenceManager $formPersistenceManager,
        protected readonly ExtFormConfigurationManagerInterface $extFormConfigurationManager,
        protected readonly ConfigurationManagerInterface $configurationManager,
    ) {}

    /**
     * Makes sure that field identifiers are unique (identifiers of deleted fields are not reused)
     * Field state is saved to keep track of old fields
     *
     * @param string $formPersistenceIdentifierBeforeSave
     * @param array<array-key, mixed> $formDefinition
     * @return array<array-key, mixed>
     * @throws PrototypeNotFoundException
     * @throws RenderingException
     * @throws TypeDefinitionNotFoundException
     * @throws TypeDefinitionNotValidException
     * @throws Exception
     */
    public function updateNewFields(string $formPersistenceIdentifierBeforeSave, array $formDefinition): array
    {
        $this->setExistingFieldStateBeforeSave($formPersistenceIdentifierBeforeSave);
        $formStateDidAlreadyExist = (bool)($formDefinition['renderingOptions']['fieldState'] ?? false);
        FormDefinitionUtility::addFieldStateIfDoesNotExist($formDefinition, true);

        // Only process if formState already existed - else no changes should be considered
        if ($formStateDidAlreadyExist) {
            //Make map of next identifier for each field type
            $this->makeNextIdentifiersMap($this->existingFieldStateBeforeSave);

            foreach (FormDefinitionUtility::convertFormDefinitionToObject($formDefinition)->getRenderablesRecursively() as $renderable) {
                if ($renderable instanceof CompositeRenderableInterface) {
                    continue;
                }
                if (
                    !($this->existingFieldStateBeforeSave[$renderable->getIdentifier()] ?? false)
                    ||
                    ($this->existingFieldStateBeforeSave[$renderable->getIdentifier()]['renderingOptions']['deleted'] ?? 0) === 1
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
     * @param array<array-key, mixed> $fieldState
     */
    protected function makeNextIdentifiersMap(array $fieldState): void
    {

        foreach ($fieldState as $identifier => &$field) {
            // Do not consider new fields
            if (!isset($this->existingFieldStateBeforeSave[$identifier])) {
                continue;
            }

            $identifierParts = explode('-', $field['identifier']);
            $identifierText = $identifierParts[0];
            $identifierNumber = $identifierParts[1] ?? '0';
            if ($identifierText !== strtolower($field['type'])) {
                continue;
            }
            if (!isset($this->fieldTypesNextIdentifier[$field['type']])) {
                $this->fieldTypesNextIdentifier[$field['type']] = [
                    'text' => $identifierText,
                    'number' => $identifierNumber,
                ];
            } else {
                $this->fieldTypesNextIdentifier[$field['type']] = [
                    'text' => $identifierText,
                    'number' => max($this->fieldTypesNextIdentifier[$field['type']]['number'], $identifierNumber),
                ];
            }
        }
        unset($field);
        array_walk($this->fieldTypesNextIdentifier, static function (&$val): void {
            $val['number']++;
        });
    }

    /**
     * @param array<array-key, mixed> $renderables
     * @param RenderableInterface $newFieldObject
     */
    protected function updateNewFieldWithNextIdentifier(array &$renderables, RenderableInterface &$newFieldObject): bool
    {
        if ($renderables !== []) {
            foreach ($renderables as &$renderable) {
                if (isset($renderable['renderables'])) {
                    if ($this->updateNewFieldWithNextIdentifier($renderable['renderables'], $newFieldObject)) {
                        return true;
                    }
                } else {
                    if ($renderable['identifier'] == $newFieldObject->getIdentifier()) {
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
     * @param array{
     *     renderingOptions: array{
     *       fieldState: array<array-key, array{
     *          renderingOptions?: array<array-key, mixed>,
     *          identifier: string
     *       }>
     *   }
     * } $formDefinition
     */
    protected function updateStateDeletedState(array &$formDefinition): void
    {
        $formDefinition['renderingOptions']['fieldState'] = array_map(function ($field) {
            $field['renderingOptions']['deleted'] = in_array($field['identifier'], $this->activeFields, true) ? 0 : 1;
            return $field;
        }, $formDefinition['renderingOptions']['fieldState']);
    }

    /**
     * @return array<array-key, mixed>
     */
    protected function getFormSettings(): array
    {
        $typoScriptSettings = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS, 'form');
        $formSettings = $this->extFormConfigurationManager->getYamlConfiguration($typoScriptSettings, false);
        if (!isset($formSettings['formManager'])) {
            // Config sub array formManager is crucial and should always exist. If it does
            // not, this indicates an issue in config loading logic. Except in this case.
            throw new \LogicException('Configuration could not be loaded', 1681549038);
        }
        return $formSettings;
    }

    /**
     * @param string $formPersistenceIdentifier
     */
    protected function setExistingFieldStateBeforeSave(string $formPersistenceIdentifier): void
    {
        $formSettings = $this->getFormSettings();
        $formDefinitionBeforeSave = $this->formPersistenceManager->load($formPersistenceIdentifier, $formSettings, []);
        $this->existingFieldStateBeforeSave = $formDefinitionBeforeSave['renderingOptions']['fieldState'] ?? [];
    }
}
