<?php /** @noinspection PhpUnusedParameterInspection */
/** @noinspection PhpInternalEntityUsedInspection */

/**
 * This file is part of the "form_to_database" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace LiquidLight\FormToDatabase\Hooks;

use LiquidLight\FormToDatabase\Domain\Model\FormResult;
use LiquidLight\FormToDatabase\Domain\Repository\FormResultRepository;
use LiquidLight\FormToDatabase\Utility\UniqueFieldHandler;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use TYPO3\CMS\Form\Mvc\Configuration\YamlSource;
use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManager;
use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManagerInterface;

/**
 * Class FormHooks
 *
 * @package LiquidLight\FormToDatabase\Hooks
 */
class FormHooks
{
    /**
     * @var ObjectManager
     */
    protected  $objectManager;

    /**
     * @var FormPersistenceManager
     */
    protected  $formPersistenceManager;

    /**
     * The FormResultRepository
     *
     * @var FormResultRepository
     */
    public  $formResultRepository;

    /**
     * Injects the FormResultRepository
     */
    public function initializeFormResultRepository(): void
    {
        $this->formResultRepository = $this->objectManager->get(FormResultRepository::class);
    }

    /**
     * Injects necessary objects into the formPersistenceManager
     */
    protected function initializeFormPersistenceManager(): void
    {
        /** @var FormPersistenceManagerInterface $formPersistenceManager */
        $this->formPersistenceManager = GeneralUtility::makeInstance(FormPersistenceManager::class);
        $this->formPersistenceManager->initializeObject();
        $this->formPersistenceManager->injectResourceFactory(GeneralUtility::makeInstance(ResourceFactory::class));

        /** @var StorageRepository $storageRepository */
        $storageRepository = $this->objectManager->get(StorageRepository::class);
        $this->formPersistenceManager->injectStorageRepository($storageRepository);

        /** @var YamlSource $yamlSource */
        $yamlSource = $this->objectManager->get(YamlSource::class);
        $this->formPersistenceManager->injectYamlSource($yamlSource);
    }

    /**
     * @param $formPersistenceIdentifier
     * @return void
     * @throws IllegalObjectTypeException
     * @throws UnknownObjectException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     * @throws \TYPO3\CMS\Form\Mvc\Persistence\Exception\PersistenceManagerException
     * @noinspection PhpParamsInspection
     * @noinspection PhpUndefinedMethodInspection
     */
    public function beforeFormDelete($formPersistenceIdentifier): void
    {
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->initializeFormResultRepository();
        $this->initializeFormPersistenceManager();
        $yaml = $this->formPersistenceManager->load($formPersistenceIdentifier);

        /** @var File $file */
        $file = ResourceFactory::getInstance()->getFileObjectFromCombinedIdentifier($formPersistenceIdentifier);

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
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        return  $this->objectManager->get(UniqueFieldHandler::class)->updateNewFields($formPersistenceIdentifier, $formDefinition);
    }
}
