<?php

/**
 * This file is part of the "form_to_database" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Lavitto\FormToDatabase\Hooks;

use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
use TYPO3\CMS\Form\Mvc\Persistence\Exception\PersistenceManagerException;
use Lavitto\FormToDatabase\Domain\Model\FormResult;
use Lavitto\FormToDatabase\Domain\Repository\FormResultRepository;
use Lavitto\FormToDatabase\Utility\FormDefinitionUtility;
use Lavitto\FormToDatabase\Utility\UniqueFieldHandler;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManager;

/**
 * Class FormHooks
 *
 * todo: split hooks into separate files and load only necessary dependencies
 * @package Lavitto\FormToDatabase\Hooks
 */
class FormHooks
{
    /**
     * @var FormDefinitionUtility
     */
    public $formDefinitionUtility;

    /**
     * @var ResourceFactory
     */
    protected ResourceFactory $resourceFactory;

    /*
     * @var UniqueFieldHandler
     */
    protected UniqueFieldHandler $uniqueFieldHandler;

    /**
     * @var FormPersistenceManager
     */
    protected FormPersistenceManager $formPersistenceManager;

    /**
     * The FormResultRepository
     * @var FormResultRepository
     */
    public FormResultRepository $formResultRepository;

    /**
     * @param FormDefinitionUtility $formDefinitionUtility
     * @param ResourceFactory $resourceFactory
     * @param UniqueFieldHandler $uniqueFieldHandler
     * @param FormPersistenceManager $formPersistenceManager
     * @param FormResultRepository $formResultRepository
     */
    public function __construct(FormDefinitionUtility $formDefinitionUtility, ResourceFactory $resourceFactory, UniqueFieldHandler $uniqueFieldHandler, FormPersistenceManager $formPersistenceManager, FormResultRepository $formResultRepository)
    {
        $this->formDefinitionUtility = $formDefinitionUtility;
        $this->resourceFactory = $resourceFactory;
        $this->uniqueFieldHandler = $uniqueFieldHandler;
        $this->formPersistenceManager = $formPersistenceManager;
        $this->formResultRepository = $formResultRepository;
    }


    /**
     * @param $formPersistenceIdentifier
     * @return void
     * @throws IllegalObjectTypeException
     * @throws UnknownObjectException
     * @throws InvalidQueryException
     * @throws PersistenceManagerException
     * @noinspection PhpParamsInspection
     */
    public function beforeFormDelete($formPersistenceIdentifier): void
    {
        $yaml = $this->formPersistenceManager->load($formPersistenceIdentifier);

        /** @var File $file */
        $file = $this->resourceFactory->getFileObjectFromCombinedIdentifier($formPersistenceIdentifier);

        //Generate new identifier
        $oldIdentifier = $yaml['identifier'];
        $cleanedIdentifier = preg_replace('/(.*)-([a-z0-9]{13})/', '$1', $yaml['identifier']);
        $newIdentifier = uniqid($cleanedIdentifier . '-', true);

        // Set new unique filename and update form definition with new identifier
        $newFilename = $newIdentifier . '.form.yaml.deleted';
        $yaml['identifier'] = $newIdentifier;
        $this->formPersistenceManager->save($formPersistenceIdentifier, $yaml);

        if ($file !== null) {
            $newCombinedIdentifier = $file->copyTo($file->getParentFolder(), $newFilename)->getCombinedIdentifier();
            /** @var QueryResult $results */
            $results = $this->formResultRepository->findByFormPersistenceIdentifier($formPersistenceIdentifier);
            /** @var FormResult $result */
            foreach ($results as $result) {
                $result->setFormPersistenceIdentifier($newCombinedIdentifier);
                $result->setFormIdentifier($newIdentifier);
                $this->formResultRepository->update($result);
            }
        }
        //Restore form definition with old identifier, so that the file to be deleted can be found by original identifier
        $yaml['identifier'] = $oldIdentifier;
        $this->formPersistenceManager->save($formPersistenceIdentifier, $yaml);
    }

    /**
     * Keep track of field identifiers of deleted and new fields, so that identifiers are not reused
     *
     * @param $formPersistenceIdentifier
     * @param $formDefinition
     * @return mixed
     */
    public function beforeFormSave($formPersistenceIdentifier, $formDefinition)
    {
        return $this->uniqueFieldHandler->updateNewFields($formPersistenceIdentifier, $formDefinition);
    }
}
