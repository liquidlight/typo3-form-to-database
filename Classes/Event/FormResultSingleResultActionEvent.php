<?php

declare(strict_types=1);

namespace LiquidLight\FormToDatabase\Event;

use LiquidLight\FormToDatabase\Domain\Model\FormResult;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;

final class FormResultSingleResultActionEvent
{
    private string $formPersistenceIdentifier;
    private FormResult $formResult;
    private FormDefinition $formDefinition;

    /**
     * @var array<string, mixed>
     */
    private array $formRenderables;
    private string $action;

    /**
     * @param array<string, mixed> $formRenderables
     */
    public function __construct(string $formPersistenceIdentifier, FormResult $formResult, FormDefinition $formDefinition, array $formRenderables, string $action = 'show')
    {
        $this->formPersistenceIdentifier = $formPersistenceIdentifier;
        $this->formResult = $formResult;
        $this->formDefinition = $formDefinition;
        $this->formRenderables = $formRenderables;
        $this->action = $action;
    }

    public function getFormPersistenceIdentifier(): string
    {
        return $this->formPersistenceIdentifier;
    }

    public function getFormResult(): FormResult
    {
        return $this->formResult;
    }

    public function getFormDefinition(): FormDefinition
    {
        return $this->formDefinition;
    }

    /**
     * @return array<string, mixed>
     */
    public function getFormRenderables(): array
    {
        return $this->formRenderables;
    }

    public function getAction(): string
    {
        return $this->action;
    }
}
