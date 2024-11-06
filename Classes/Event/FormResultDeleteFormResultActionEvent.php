<?php

namespace Lavitto\FormToDatabase\Event;

use Lavitto\FormToDatabase\Domain\Model\FormResult;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;

final class FormResultDeleteFormResultActionEvent
{

    /**
     * @param string $formPersistenceIdentifier
     * @param FormResult $formResult
     * @param FormDefinition $formDefinition
     * @param array<array-key, mixed> $formRenderables
     */
    public function __construct(
        private readonly string         $formPersistenceIdentifier,
        private readonly FormResult     $formResult,
        private readonly FormDefinition $formDefinition,
        private readonly array          $formRenderables
    )
    {
    }

    /**
     * @return string
     */
    public function getFormPersistenceIdentifier(): string
    {
        return $this->formPersistenceIdentifier;
    }

    /**
     * @return FormResult
     */
    public function getFormResult(): FormResult
    {
        return $this->formResult;
    }

    /**
     * @return FormDefinition
     */
    public function getFormDefinition(): FormDefinition
    {
        return $this->formDefinition;
    }

    /**
     * @return array<array-key,mixed>
     */
    public function getFormRenderables(): array
    {
        return $this->formRenderables;
    }
}
