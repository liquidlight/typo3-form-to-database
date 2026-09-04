<?php

declare(strict_types=1);

/**
 * This file is part of the "form_to_database" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace LiquidLight\FormToDatabase\EventListener;

use LiquidLight\FormToDatabase\Domain\Model\FormResult;
use LiquidLight\FormToDatabase\Domain\Repository\FormResultRepository;
use LiquidLight\FormToDatabase\Utility\FormPersistenceIdentifierUtility;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
use TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use TYPO3\CMS\Form\Event\BeforeFormIsDeletedEvent;
use TYPO3\CMS\Form\Mvc\Persistence\Exception\PersistenceManagerException;
use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManager;

#[AsEventListener(identifier: 'form-to-database/before-form-is-deleted')]
final class BeforeFormIsDeletedEventListener
{
    public function __construct(
        private readonly ResourceFactory $resourceFactory,
        private readonly FormPersistenceManager $formPersistenceManager,
        private readonly FormResultRepository $formResultRepository,
    ) {}

    /**
     * @throws IllegalObjectTypeException
     * @throws UnknownObjectException
     * @throws InvalidQueryException
     * @throws PersistenceManagerException
     */
    public function __invoke(BeforeFormIsDeletedEvent $event): void
    {
        $formPersistenceIdentifier = $event->formPersistenceIdentifier;

        if (FormPersistenceIdentifierUtility::isDatabaseStored($formPersistenceIdentifier)) {
            // Database-stored forms (TYPO3\CMS\Form\Storage\DatabaseStorageAdapter) are soft-deleted
            // by DataHandler and keep their uid as persistence identifier, so linked FormResult rows
            // stay valid without any rewriting here. The rename-dance below is file-storage-only —
            // ResourceFactory::getFileObjectFromCombinedIdentifier() would fail on a bare numeric id.
            return;
        }

        // empty form settings correct here, as an empty array will allow all
        // entry points. This is, what is better at this point
        $formSettings = [];
        $yaml = $this->formPersistenceManager->load($formPersistenceIdentifier);

        /** @var File $file */
        $file = $this->resourceFactory->getFileObjectFromCombinedIdentifier($formPersistenceIdentifier);

        // Generate new identifier
        $oldIdentifier = $yaml['identifier'];
        $cleanedIdentifier = preg_replace('/(.*)-([a-z0-9]{13})/', '$1', $yaml['identifier']);
        $newIdentifier = uniqid($cleanedIdentifier . '-', true);

        // Set new unique filename and update form definition with new identifier
        $newFilename = $newIdentifier . '.form.yaml.deleted';
        $yaml['identifier'] = $newIdentifier;
        $this->formPersistenceManager->save($formPersistenceIdentifier, $yaml, $formSettings);

        if ($file !== null) {
            $newCombinedIdentifier = $file->copyTo($file->getParentFolder(), $newFilename)->getCombinedIdentifier();
            /** @var QueryResult<FormResult> $results */
            $results = $this->formResultRepository->findByFormPersistenceIdentifier($formPersistenceIdentifier);
            foreach ($results as $result) {
                $result->setFormPersistenceIdentifier($newCombinedIdentifier);
                $result->setFormIdentifier($newIdentifier);
                $this->formResultRepository->update($result);
            }
        }
        // Restore form definition with old identifier, so that the file to be deleted can be found by original identifier
        $yaml['identifier'] = $oldIdentifier;
        $this->formPersistenceManager->save($formPersistenceIdentifier, $yaml, $formSettings);
    }
}
