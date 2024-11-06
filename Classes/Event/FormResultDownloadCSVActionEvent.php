<?php

namespace Lavitto\FormToDatabase\Event;

use Lavitto\FormToDatabase\Domain\Model\FormResult;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;

final class FormResultDownloadCSVActionEvent
{
    /**
     * @param string $formPersistenceIdentifier
     * @param QueryResultInterface<FormResult> $formResults
     * @param FormDefinition $formDefinition
     * @param array<array-key, mixed> $formRenderables
     */
    public function __construct(
        private readonly string               $formPersistenceIdentifier,
        private readonly QueryResultInterface $formResults,
        private readonly FormDefinition       $formDefinition,
        private readonly array $formRenderables
    ) {
    }

    /**
     * @return string
     */
    public function getFormPersistenceIdentifier(): string
    {
        return $this->formPersistenceIdentifier;
    }

    /**
     * @return QueryResultInterface<FormResult>
     */
    public function getFormResults(): QueryResultInterface
    {
        return $this->formResults;
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
