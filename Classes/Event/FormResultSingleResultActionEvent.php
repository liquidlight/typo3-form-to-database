<?php

namespace Lavitto\FormToDatabase\Event;

use Lavitto\FormToDatabase\Domain\Model\FormResult;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;

final class FormResultSingleResultActionEvent
{
    private string $formPersistenceIdentifier;
    private FormResult $formResult;
    private FormDefinition $formDefinition;
    private array $formRenderables;
    private string $action;

    /**
     * @param string $formPersistenceIdentifier
     * @param FormResult $formResult
     * @param FormDefinition $formDefinition
     * @param array $formRenderables
     */
    public function __construct(string $formPersistenceIdentifier, FormResult $formResult, FormDefinition $formDefinition, array $formRenderables, string $action = 'show')
    {
        $this->formPersistenceIdentifier = $formPersistenceIdentifier;
        $this->formResult = $formResult;
        $this->formDefinition = $formDefinition;
        $this->formRenderables = $formRenderables;
        $this->action = $action;
    }

    /**
     * @return string
     */
    public function getFormPersistenceIdentifier(): string
    {
        return $this->formPersistenceIdentifier;
    }

    /**
     * @return QueryResult
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
     * @return array
     */
    public function getFormRenderables(): array
    {
        return $this->formRenderables;
    }

    /**
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }
}