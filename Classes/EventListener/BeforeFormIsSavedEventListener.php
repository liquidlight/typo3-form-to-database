<?php

declare(strict_types=1);

/**
 * This file is part of the "form_to_database" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace LiquidLight\FormToDatabase\EventListener;

use LiquidLight\FormToDatabase\Utility\UniqueFieldHandler;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Form\Event\BeforeFormIsSavedEvent;

#[AsEventListener(identifier: 'form-to-database/before-form-is-saved')]
final class BeforeFormIsSavedEventListener
{
    public function __construct(
        private readonly UniqueFieldHandler $uniqueFieldHandler,
    ) {}

    /**
     * Keep track of field identifiers of deleted and new fields, so that identifiers are not reused
     */
    public function __invoke(BeforeFormIsSavedEvent $event): void
    {
        $event->form = $this->uniqueFieldHandler->updateNewFields($event->formPersistenceIdentifier, $event->form);
    }
}
