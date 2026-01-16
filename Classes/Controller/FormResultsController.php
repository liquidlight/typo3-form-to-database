<?php /** @noinspection PhpUndefinedMethodInspection */
/** @noinspection AdditionOperationOnArraysInspection */
/** @noinspection PhpInternalEntityUsedInspection */

/**
 * This file is part of the "form_to_database" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace LiquidLight\FormToDatabase\Controller;

use DateTime;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\FetchMode;
use Exception;
use LiquidLight\FormToDatabase\Domain\Finishers\FormToDatabaseFinisher;
use LiquidLight\FormToDatabase\Domain\Model\FormResult;
use LiquidLight\FormToDatabase\Domain\Repository\FormResultRepository;
use LiquidLight\FormToDatabase\Event\FormResultDeleteFormResultActionEvent;
use LiquidLight\FormToDatabase\Event\FormResultDownloadCSVActionEvent;
use LiquidLight\FormToDatabase\Event\FormResultShowActionEvent;
use LiquidLight\FormToDatabase\Event\FormResultSingleResultActionEvent;
use LiquidLight\FormToDatabase\Helpers\MiscHelper;
use LiquidLight\FormToDatabase\Service\FormResultDatabaseService;
use LiquidLight\FormToDatabase\Utility\ExtConfUtility;
use LiquidLight\FormToDatabase\Utility\FormDefinitionUtility;
use LiquidLight\FormToDatabase\Utility\FormValueUtility;
use LiquidLight\FormToDatabase\Utility\PdfUtility;
use Mpdf\Mpdf;
use PDO;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Pagination\ArrayPaginator;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\Resource\Driver\LocalDriver;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
use TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException;
use TYPO3\CMS\Form\Controller\FormManagerController;
use TYPO3\CMS\Form\Domain\Exception\RenderingException;
use TYPO3\CMS\Form\Domain\Factory\ArrayFormFactory;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\FormElements\AbstractFormElement;
use TYPO3\CMS\Form\Slot\FilePersistenceSlot;

/**
 * Class FormResultsController
 *
 * @package LiquidLight\FormToDatabase\Controller
 */
class FormResultsController extends FormManagerController
{
    /**
     * CSV Linebreak
     */
    protected const CSV_LINEBREAK = "\n";

    /**
     * CSV Text Enclosure
     */
    protected const CSV_ENCLOSURE = '"';

    public const defaultNumberOfColumnsInListView = 4;


    /**
     * @var ExtConfUtility
     */
    protected ExtConfUtility $extConfUtility;

    /**
     * @var FormResultRepository
     */
    protected FormResultRepository $formResultRepository;

    /**
     * @var BackendUserAuthentication
     */
    protected BackendUserAuthentication $BEUser;

    /**
     * @var FormResultDatabaseService
     */
    protected FormResultDatabaseService $formResultDatabaseService;

    /**
     * @var ModuleTemplate
     */
    protected ModuleTemplate $moduleTemplate;

    /**
     * Injects the FormResultRepository
     *
     * @param FormResultRepository $formResultRepository
     */
    public function injectFormResultRepository(FormResultRepository $formResultRepository): void
    {
        $this->formResultRepository = $formResultRepository;
    }

    /**
     * Injects the FormResultRepository
     *
     * @param FormResultDatabaseService $formResultDatabaseService
     */
    public function injectFormResultDatabaseService(FormResultDatabaseService $formResultDatabaseService): void
    {
        $this->formResultDatabaseService = $formResultDatabaseService;
    }

    /**
     * Injects the ExtConfUtility
     *
     * @param ExtConfUtility $extConfUtility
     */
    public function injectExtConfUtility(ExtConfUtility $extConfUtility): void
    {
        $this->extConfUtility = $extConfUtility;
    }

    /**
     * @return void
     */
    protected function initializeAction()
    {
        $this->BEUser = $GLOBALS['BE_USER'];
    }

    /**
     * Displays the Form Overview
     *
     * @throws InvalidQueryException|DBALException
     * @internal
     * @noinspection PhpUndefinedMethodInspection
     */
    public function indexAction(int $page = 1): ResponseInterface
    {
        $this->moduleTemplate = $this->moduleTemplateFactory->create($this->request);

        $availableFormDefinitions = [];
        $searchKey = $this->request->getParsedBody()['tx_form_to_database']['search'] ?? '';
        if (empty($searchKey)) {
            $availableFormDefinitions = $this->getAvailableFormDefinitions();
        } else {
            foreach ($this->getAvailableFormDefinitions() as $formDefinition) {
                foreach (['name'] as $searchField) {
                    if (
                        is_string($formDefinition[$searchField])
                        && str_contains(
                            strtolower($formDefinition[$searchField]),
                            strtolower($searchKey)
                        )
                    ) {
                        $availableFormDefinitions[$formDefinition['identifier']] = $formDefinition;
                    }
                }
            }
        }

        $this->registerDocheaderButtons();
        $this->enrichFormDefinitionsWithHighestCrDate($availableFormDefinitions);
        $this->view->assign('forms', $availableFormDefinitions);
        $this->view->assign('searchKey', $searchKey);
        $this->view->assign('deletedForms', $this->getDeletedFormDefinitions($availableFormDefinitions));
        $this->assignDefaults();

        $this->moduleTemplate->setModuleName($this->request->getPluginName() . '_' . $this->request->getControllerName());
        $this->moduleTemplate->setFlashMessageQueue($this->getFlashMessageQueue(FlashMessageQueue::NOTIFICATION_QUEUE));
        $this->moduleTemplate->setContent($this->view->render());

        return $this->htmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * @param $formDefinition
     * @return mixed|null
     */
    private function getCurrentBEUserLastViewTime($formDefinition)
    {
        $identifier = is_array($formDefinition) ? $formDefinition['identifier'] : $formDefinition->getIdentifier();
        return $this->BEUser->uc['tx_formtodatabase']['lastView'][$identifier] ?? null;
    }

    /**
     * @param $formDefinitions
     * @throws DBALException
     */
    private function enrichFormDefinitionsWithHighestCrDate(&$formDefinitions): void
    {
        $identifiers = array_column($formDefinitions, 'identifier');
        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $table = 'tx_formtodatabase_domain_model_formresult';
        $qb = $connectionPool->getQueryBuilderForTable($table);
        $result = $qb->select('form_identifier')
            ->addSelectLiteral(
                $qb->expr()->max('crdate', 'maxcrdate')
            )
            ->from($table)
            ->where($qb->expr()->in(
                'form_identifier',
                $qb->createNamedParameter($identifiers, Connection::PARAM_STR_ARRAY))
            )->groupBy('form_identifier')->executeQuery()->fetchAll(FetchMode::NUMERIC);
        $maxCrDates = array_combine(array_column($result, 0), array_column($result, 1));
        foreach ($formDefinitions as &$formDefinition) {
            $formDefinition['maxCrDate'] = $maxCrDates[$formDefinition['identifier']] ?? null;
            $formDefinition['newDataExists'] = $formDefinition['maxCrDate'] > $this->getCurrentBEUserLastViewTime($formDefinition);
        }
    }

    /**
     * Shows the results of a form
     *
     * @param string $formPersistenceIdentifier
     * @return ResponseInterface
     * @throws InvalidQueryException
     * @throws RenderingException
     * @noinspection PhpUndefinedMethodInspection
     * @noinspection PhpUnused
     */
    public function showAction(string $formPersistenceIdentifier): ResponseInterface
    {
        $fieldsWithData = [];
        $this->moduleTemplate = $this->moduleTemplateFactory->create($this->request);

        $currentPage = $this->request->getArguments()['currentPage'] ?? 1;
        $newDataExists = false;
        $languageFile = 'LLL:EXT:form_to_database/Resources/Private/Language/locallang_be.xlf:';

        $this->pageRenderer->addCssFile(
            'EXT:form_to_database/Resources/Public/Css/ShowPrintStyles.min.css',
            'stylesheet',
            'print'
        );
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Modal');
        $this->pageRenderer->addInlineLanguageLabelArray([
            'ftd_deleteTitle' => $this->getLanguageService()->sL($languageFile . 'show.buttons.delete.title'),
            'ftd_deleteDescription' => $this->getLanguageService()->sL($languageFile . 'show.buttons.delete.description')
        ]);

        $formResults = $this->formResultRepository->findByFormPersistenceIdentifier($formPersistenceIdentifier);
        $formDefinition = $this->getFormDefinitionObject($formPersistenceIdentifier, true);
        $formRenderables = $this->getFormRenderables($formDefinition);
        $lastView = $this->getCurrentBEUserLastViewTime($formDefinition);
        //Find if any new data exists
        if ($lastView) {
            foreach ($formResults as $formResult) {
                if ($formResult->getCrdate() > new DateTime("@$lastView")) {
                    $newDataExists = true;
                }
            }
        }

        /** @var FormResult $formResult */
        foreach ($formResults as $formResult) {
            foreach ($formDefinition->getRenderingOptions()['fieldState'] ?? [] as $fieldIdentifier => $_) {
                if (!empty(FormValueUtility::findValuesByIdentifier($formResult->getResultAsArray(), $fieldIdentifier))) {
                    $fieldsWithData[$fieldIdentifier] = 1;
                }
            }
        }

        $fieldsWithNoData = array_diff_key(array_fill_keys(array_keys($formDefinition->getRenderingOptions()['fieldState'] ?? []), 1), $fieldsWithData);

        /** @var FormResultShowActionEvent $event */
        $this->eventDispatcher->dispatch(
            new FormResultShowActionEvent(
                $formPersistenceIdentifier,
                $formResults,
                $formDefinition,
                $formRenderables
            )
        );

        $paginator = new ArrayPaginator($formResults->toArray(), $currentPage, 20);
        $pagination = new SimplePagination($paginator);

        $this->registerDocheaderButtons($formPersistenceIdentifier, $formResults->count() > 0);
        $this->view->assignMultiple([
            'formResults' => $formResults,
            'formDefinition' => $formDefinition,
            'formRenderables' => $formRenderables,
            'formPersistenceIdentifier' => $formPersistenceIdentifier,
            'newDataExists' => $newDataExists,
            'lastView' => $lastView,
            'paginator' => $paginator,
            'pagination' => $pagination,
            'fieldsWithData' => $fieldsWithData,
            'fieldsWithNoData' => $fieldsWithNoData,
            'extConfig' => $this->extConfUtility->getFullConfig()
        ]);
        $this->assignDefaults();

        // For current formDefinition, add/replace lastView timestamp to uc with current time
        $this->BEUser->uc['tx_formtodatabase']['lastView'][$formDefinition->getIdentifier()] = time();
        $this->BEUser->writeUC();

        $this->moduleTemplate->setContent($this->view->render());

        return $this->htmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Shows the results of a form
     *
     * @param string $uid
     * @return ResponseInterface
     * @throws InvalidQueryException
     * @throws RenderingException
     * @noinspection PhpUndefinedMethodInspection
     * @noinspection PhpUnused
     */
    public function resultAction(string $uid): ResponseInterface
    {
        $this->moduleTemplate = $this->moduleTemplateFactory->create($this->request);

        $variables = $this->getSingleResultProperties($uid);

        $this->registerDocheaderButtons($variables['formPersistenceIdentifier']);

        $this->moduleTemplate->setContent($this->view->render());

        return $this->htmlResponse($this->moduleTemplate->renderContent());
    }

    public function downloadResultPdfAction(string $uid): ResponseInterface
    {
        if(!class_exists(Mpdf::class)) {
            $this->htmlResponse('');
        }

        $excludeFields = array_diff(FormToDatabaseFinisher::EXCLUDE_FIELDS, ['GridRow', 'SummaryPage', 'Page']);
        $variables = $this->getSingleResultProperties($uid, $excludeFields);

        if(isset($this->settings['pdf']['disable']) && (int)$this->settings['pdf']['disable'] === 1) {
            $this->moduleTemplate = $this->moduleTemplateFactory->create($this->request);
            $this->moduleTemplate->getDocHeaderComponent()->disable();
            $this->moduleTemplate->setContent($this->view->render());

            return $this->htmlResponse($this->moduleTemplate->renderContent());
        }

        $pdfUtility = GeneralUtility::makeInstance(PdfUtility::class, $this->settings['pdf'] ?? []);

        $filePath = $pdfUtility->generatePdf(
            $this->view->render(),
            $variables['formDefinition']->getIdentifier() . '-' . $variables['formResult']->getCrDate()->format('U')
        );

        $destination = ($this->settings['pdf']['disposition'] ?? 'attachment') === 'attachment' ? 'attachment' : 'inline';

        $response = new Response();
        $response = $response->withHeader('Content-Transfer-Encoding', 'binary');
        $response->getBody()->write(file_get_contents($filePath));
        $response = $response->withHeader('Content-Type', 'application/pdf');
        $response = $response->withHeader('Content-Disposition', $destination . '; filename="' . basename($filePath) . '"');

        return $response->withStatus(200);
    }

    /**
     * Downloads the current results list as CSV
     *
     * @throws NoSuchArgumentException
     * @throws Exception
     * @todo Add more charsets?
     */
    public function downloadCsvAction(): ResponseInterface
    {
        $charset = 'UTF-8';
        $formPersistenceIdentifier = $this->request->getArgument('formPersistenceIdentifier');
        $filtered = $this->request->hasArgument('filtered') === true && $this->request->getArgument('filtered') === '1';

        $csvContent = "\xEF\xBB\xBF" . $this->getCsvContent($formPersistenceIdentifier, $filtered);

        return $this->responseFactory
            ->createResponse()
            ->withHeader(
                'Content-Type',
                sprintf('application/json; charset=%s', $charset ?? 'utf-8')
            )
            ->withHeader(
                'Content-Disposition',
                sprintf('attachment; filename="%s";', $this->getCsvFileName($formPersistenceIdentifier))
            )
            ->withHeader(
                'Content-Length',
                (string)strlen($csvContent)
            )
            ->withBody($this->streamFactory->createStream((string)($csvContent)));

    }

    /**
     * Deletes a form result and forwards to the show action
     *
     * @param FormResult $formResult
     * @throws IllegalObjectTypeException
     * @throws RenderingException
     * @throws StopActionException
     */
    public function deleteFormResultAction(FormResult $formResult): ResponseInterface
    {
        $formPersistenceIdentifier = $formResult->getFormPersistenceIdentifier();
        $this->formResultRepository->remove($formResult);
        $formDefinition = $this->getFormDefinitionObject($formPersistenceIdentifier);

        $this->eventDispatcher->dispatch(
            new FormResultDeleteFormResultActionEvent(
                $formPersistenceIdentifier,
                $formResult,
                $formDefinition,
                $this->getFormRenderables($formDefinition)
            )
        );

        return new RedirectResponse($this->uriBuilder->uriFor(
            'show',
            ['formPersistenceIdentifier' => $formPersistenceIdentifier]
        ));
    }

    /**
     * @param string $formDefinitionPath
     * @param string $formIdentifier
     * @throws IllegalObjectTypeException
     * @throws StopActionException
     * @throws UnknownObjectException
     * @noinspection PhpParamsInspection
     */
    public function unDeleteFormDefinitionAction(string $formDefinitionPath, string $formIdentifier): RedirectResponse
    {
        /** @var FilePersistenceSlot $formPersistenceSlot */
        $formPersistenceSlot = GeneralUtility::makeInstance(FilePersistenceSlot::class);
        $formPersistenceSlot->allowInvocation(
            FilePersistenceSlot::COMMAND_FILE_MOVE,
            str_replace('.deleted', '', $formDefinitionPath)
        );
        /** @var ResourceFactory $resourceFactory */
        $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);

        /** @var File $file */
        $file = $resourceFactory->getFileObjectFromCombinedIdentifier($formDefinitionPath);

        if ($file !== null) {
            $filename = "{$formIdentifier}.form.yaml";
            $newCombinedIdentifier = $file->moveTo($file->getParentFolder(), $filename)->getCombinedIdentifier();
            $results = $this->formResultRepository->findByFormIdentifier($formIdentifier);
            /** @var FormResult $result */
            foreach ($results as $result) {
                $result->setFormPersistenceIdentifier($newCombinedIdentifier);
                $this->formResultRepository->update($result);
            }
        }

        return new RedirectResponse($this->uriBuilder->uriFor(
            'index'
        ));
    }

    /**
     * @throws NoSuchArgumentException
     * @throws StopActionException
     */
    public function updateItemListSelectAction(): RedirectResponse
    {
        $formPersistenceIdentifier = $this->request->getArgument('formPersistenceIdentifier');
        $formDefinition = $this->getFormDefinition($formPersistenceIdentifier);
        /** @var FormDefinitionUtility $formDefinitionUtility */
        $formDefinitionUtility = GeneralUtility::makeInstance(FormDefinitionUtility::class);
        $formDefinitionUtility->addFieldStateIfDoesNotExist($formDefinition);

        $this->BEUser->uc['tx_formtodatabase']['listViewStates'][$formDefinition['identifier']] = $this->request->getArgument('field');
        $this->BEUser->writeUC();

        return new RedirectResponse($this->uriBuilder->uriFor(
            'show',
            ['formPersistenceIdentifier' => $this->request->getArgument('formPersistenceIdentifier')]
        ));
    }

    /**
     * List all formDefinitions which can be loaded form persistence manager.
     * Enrich this data by the number of results.
     *
     * @return array
     */
    protected function getAvailableFormDefinitions(): array
    {
        $formResults = $this->formResultDatabaseService->getAllFormResultsForPersistenceIdentifier();
        $availableFormDefinitions = [];
        foreach ($this->formPersistenceManager->listForms() as $formDefinition) {
            $form = $this->formPersistenceManager->load($formDefinition['persistenceIdentifier']);
            $finisherInVariant = false;
            if (isset($form['variants'])) {
                foreach ($form['variants'] as $variant) {
                    if (
                        !empty($variant['finishers']) &&
                        in_array('FormToDatabase', array_column($variant['finishers'], 'identifier'), true)
                    ) {
                        $finisherInVariant = true;
                        break;
                    }
                }
            }
            if (
                (
                    !empty($form['finishers']) &&
                    in_array('FormToDatabase', array_column($form['finishers'], 'identifier'), true)
                ) ||
                $finisherInVariant
            ) {
                $formDefinition['numberOfResults'] = $formResults[$formDefinition['persistenceIdentifier']] ?? 0;
                $availableFormDefinitions[] = $formDefinition;
            }
        }
        return $availableFormDefinitions;
    }

    /**
     * List all representations of deleted formDefinitions which can be found in FormResults but not from persistence manager.
     * Enrich this data by a the number of results.
     *
     * @param array $availableFormDefinitions
     * @return array
     */
    protected function getDeletedFormDefinitions(array $availableFormDefinitions): array
    {
        $accessibleDeletedFormDefinitions = [];
        $storageFolders = $this->formPersistenceManager->getAccessibleFormStorageFolders();
        /** @var FileExtensionFilter $filter */
        $filter = GeneralUtility::makeInstance(FileExtensionFilter::class);
        $filter->setAllowedFileExtensions(['deleted']);
        foreach ($storageFolders as $storageFolder) {
            $storageFolder->setFileAndFolderNameFilters([[$filter, 'filterFileList']]);
            $accessibleDeletedFormDefinitions += $storageFolder->getFiles();
        }
        $accessibleDeletedFormDefinitions = array_map(static function ($val): string {
            $val = $val->getCombinedIdentifier();
            return $val;
        }, $accessibleDeletedFormDefinitions, []);
        $persistenceIdentifier = array_column($availableFormDefinitions, 'persistenceIdentifier') ?: [''];

        $webMounts = MiscHelper::getWebMounts();
        //plugins that user currently has access to
        $pluginUids = MiscHelper::getPluginUids($webMounts);
        //site admins
        $siteIdentifiers = MiscHelper::getSiteIdentifiersFromRootPids($webMounts);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_formtodatabase_domain_model_formresult');
        $result = $queryBuilder
            ->select('form_persistence_identifier', 'form_identifier')
            ->addSelectLiteral($queryBuilder->expr()->count('form_identifier', 'count'))
            ->from('tx_formtodatabase_domain_model_formresult')
            ->where(
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->in('form_plugin_uid',
                        $queryBuilder->createNamedParameter($pluginUids ?? [''], Connection::PARAM_STR_ARRAY)),
                    $queryBuilder->expr()->in('site_identifier',
                        $queryBuilder->createNamedParameter($siteIdentifiers ?? [''], Connection::PARAM_STR_ARRAY)),
                    //Backward compatibility with old data
                    $queryBuilder->expr()->and($queryBuilder->expr()->eq('site_identifier', $queryBuilder->createNamedParameter('')), $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter(0, PDO::PARAM_INT)))
                ),
                $queryBuilder->expr()->notIn('form_persistence_identifier',
                    $queryBuilder->createNamedParameter($persistenceIdentifier, Connection::PARAM_STR_ARRAY)),
                $queryBuilder->expr()->in('form_persistence_identifier',
                    $queryBuilder->createNamedParameter($accessibleDeletedFormDefinitions, Connection::PARAM_STR_ARRAY))

            )->groupBy('tx_formtodatabase_domain_model_formresult.form_persistence_identifier', 'tx_formtodatabase_domain_model_formresult.form_identifier')->executeQuery()->fetchAll();

        array_walk($result, static function (&$val): void {
            $val['name'] = $val['identifier'] = preg_replace("/.*\/(.*)-([a-z0-9]{13}).form.yaml.deleted/", '$1',
                $val['form_persistence_identifier']);
            $val['persistenceIdentifier'] = $val['form_persistence_identifier'];
        });
        return $result;
    }

    /**
     * Gets a form definition by a persistence form identifier
     *
     * @param string $formPersistenceIdentifier
     * @param bool $useFieldStateDataAsRenderables
     * @return array
     */
    protected function getFormDefinition(
        string $formPersistenceIdentifier,
        $useFieldStateDataAsRenderables = false
    ): array {
        $configuration = $this->formPersistenceManager->load($formPersistenceIdentifier);

        $this->hydrateRepeatableFields($configuration);
        $this->enrichFieldStateWithListViewStates($configuration);

        if ($useFieldStateDataAsRenderables) {
            $this->hydrateRenderablesFromFieldState($configuration);
        }

		return $configuration;
    }

    /**
     * hydrateRepeatableFields
     *
     * Creates repeated fields for any fields which could be repeated
     *
     * @param  array $configuration
     * @return void
     */
    protected function hydrateRepeatableFields(array &$configuration): void
    {
        foreach($configuration['renderables'] as $p => $pages) {
            foreach($pages['renderables'] ?? [] as $i => $renderable) {
                if(!isset(
                    $renderable['renderables'],
                    $renderable['properties'],
                    $renderable['properties']['maximumCopies']
                )) {
                    continue;
                }

                $childFields = $this->getFieldElements($renderable['renderables']);
                $renderableFields = [];

                for ($x = 0; $x < $renderable['properties']['maximumCopies']; $x++) {
                    foreach($childFields as $field) {
                        $nestedIdentifier = sprintf('%s.%s.%s', $renderable['identifier'], $x, $field['identifier']);
                        $nestedLabel = sprintf('%s (%s)', $field['label'], ($x + 1));

                        $renderableField = $field;
                        $renderableField['label'] = $nestedLabel;
                        $renderableField['identifier'] = $nestedIdentifier;
                        $renderableFields[] = $renderableField;

                        if(!isset($configuration['renderingOptions']['fieldState'][$field['identifier']])) {
                            continue;
                        }

                        $fieldStateField = $configuration['renderingOptions']['fieldState'][$field['identifier']];
                        $fieldStateField['label'] = $nestedLabel;
                        $fieldStateField['identifier'] = $nestedIdentifier;
                        $configuration['renderingOptions']['fieldState'][$nestedIdentifier] = $fieldStateField;

                        if($x === ((int)$renderable['properties']['maximumCopies'] - 1)) {
                            unset($configuration['renderingOptions']['fieldState'][$field['identifier']]);
                        }
                    }
                }

                $configuration['renderables'][$p]['renderables'][$i]['renderables'] = $renderableFields;

            }
        }
    }

    /**
     * getFieldElements
     *
     * Flatten any gridrows & fieldsets
     *
     * @param  mixed $renderables
     * @return array
     */
    protected function getFieldElements(array $renderables): array
    {
        $fields = [];
        foreach($renderables as $renderable) {
            if(isset($renderable['renderables'])) {
                $fields = array_merge($fields, $this->getFieldElements($renderable['renderables']));
            } else {
                $fields[] = $renderable;
            }
        }
        return $fields;
    }

    /**
     * Replaces the form's renderables with data from the fieldState,
     * while preserving original properties.
     *
     * @param array $configuration The form configuration, passed by reference.
     */
    protected function hydrateRenderablesFromFieldState(array &$configuration): void
    {
        //Ensure that fieldState exists
        /** @var FormDefinitionUtility $formDefinitionUtility */
        $formDefinitionUtility = GeneralUtility::makeInstance(FormDefinitionUtility::class);
        $formDefinitionUtility->addFieldStateIfDoesNotExist($configuration, true);

        // Keep a reference to the original renderables to copy properties from.
        $originalRenderables = $configuration['renderables'][0]['renderables'] ?? [];

        // Use fieldState as the new source for renderables.
        $configuration['renderables'][0]['renderables'] = array_values($configuration['renderingOptions']['fieldState']);
        $configuration['renderables'] = array_intersect_key($configuration['renderables'], [0 => 1]);

        // Create a map of original renderables indexed by identifier for faster lookup.
        $originalRenderablesById = [];
        $this->getOriginalRenderablesById($originalRenderables, $originalRenderablesById);

        //Use properties from the original renderables.
        foreach ($configuration['renderables'][0]['renderables'] as &$fieldState) {
            if (isset($fieldState['identifier'], $originalRenderablesById[$fieldState['identifier']]['properties'])) {
                $fieldState['properties'] = $originalRenderablesById[$fieldState['identifier']]['properties'];
            }
        }

        unset($fieldState);
    }

    /**
     * Create a map of original renderables indexed by identifier for faster lookup.
     *
     * @param array $originalRenderables The original renderables.
     * @param array $originalRenderablesById The original renderables indexed by identifier, passed by reference.
     */
    protected function getOriginalRenderablesById(array $originalRenderables, array &$originalRenderablesById): void
    {
        foreach ($originalRenderables as $renderable) {
            if (isset($renderable['identifier'])) {
                $originalRenderablesById[$renderable['identifier']] = $renderable;
            }

            if (isset($renderable['renderables'])) {
                $this->getOriginalRenderablesById($renderable['renderables'], $originalRenderablesById);
            }
        }
    }

    /**
     * Gets a form definition by a persistence form identifier
     *
     * @param string $formPersistenceIdentifier
     * @param bool $useFieldStateDataAsRenderables
     * @return FormDefinition
     * @throws RenderingException
     */
    protected function getFormDefinitionObject(
        string $formPersistenceIdentifier,
        $useFieldStateDataAsRenderables = false,
        array $excludeFields = FormToDatabaseFinisher::EXCLUDE_FIELDS
    ): FormDefinition {
        $configuration = $this->getFormDefinition($formPersistenceIdentifier, $useFieldStateDataAsRenderables);

        if (count($excludeFields) > 0 && isset($configuration['renderables']) && !empty($configuration['renderables'])) {
            $this->filterExcludedFormFieldsInConfiguration($configuration['renderables'], $excludeFields);
        }

        /** @var ArrayFormFactory $arrayFormFactory */
        $arrayFormFactory = GeneralUtility::makeInstance(ArrayFormFactory::class);
        return $arrayFormFactory->build($configuration);
    }

    /**
     * @param array $formDefinition
     * @return void
     */
    protected function enrichFieldStateWithListViewStates(array &$formDefinition) {
        // Set listView states from user configuration
        if($listViewStates = $this->BEUser->uc['tx_formtodatabase']['listViewStates'][$formDefinition['identifier']] ?? false) {
            foreach ($formDefinition['renderingOptions']['fieldState'] ?? [] as $identifier => $field) {
                $formDefinition['renderingOptions']['fieldState'][$identifier]['renderingOptions']['listView'] = ($listViewStates[$field['identifier']] ?? false) ? 1 : 0;
            }
        } else {
            // Prioritize old method of storing listView state, when saved in fieldState. Then return with no changes
            foreach ($formDefinition['renderingOptions']['fieldState'] ?? [] as $identifier => $field) {
                if(isset($field['renderingOptions']['listView'])) return;
            }
            // New default - if user has not selected field to view in listView, display the first {self::defaultNumberOfColumnsInListView} fields
            $count = 0;
            foreach ($formDefinition['renderingOptions']['fieldState'] ?? [] as $identifier => $field) {
                $listViewEnable = ($field['renderingOptions']['deleted'] ?? 0) == 0 && $count++ < self::defaultNumberOfColumnsInListView;
                $formDefinition['renderingOptions']['fieldState'][$identifier]['renderingOptions']['listView'] = $listViewEnable ? 1 : 0;
            }
        }
    }

    /**
     * Removes excluded renderables from configuration
     *
     * @param array $renderables
     */
    protected function filterExcludedFormFieldsInConfiguration(array &$renderables, array $excludeFields = FormToDatabaseFinisher::EXCLUDE_FIELDS): void
    {
        $filtered = [];

        foreach ($renderables as $renderable) {
            if (in_array($renderable['type'], $excludeFields, true)) {
                // Filter and merge in any valid child fields
                if (!empty($renderable['renderables']) && is_array($renderable['renderables'])) {
                    $this->filterExcludedFormFieldsInConfiguration($renderable['renderables'], $excludeFields);
                    foreach ($renderable['renderables'] as $child) {
                        $filtered[] = $child;
                    }
                }
                continue;
            }

            // Process any nested renderables
            if (!empty($renderable['renderables']) && is_array($renderable['renderables'])) {
                $this->filterExcludedFormFieldsInConfiguration($renderable['renderables'], $excludeFields);
            }

            $filtered[] = $renderable;
        }

        $renderables = $filtered;
    }

    /**
     * Gets an array of all form renderables (recursive) by a form definition
     *
     * @param FormDefinition $formDefinition
     * @return array
     */
    protected function getFormRenderables(FormDefinition $formDefinition, bool $filterFormFields = true): array
    {
        $formRenderables = [];

        /** @var AbstractFormElement $renderable */
        foreach ($formDefinition->getRenderablesRecursively() as $renderable) {
            if(
                $filterFormFields === false ||
                ($filterFormFields && $renderable instanceof AbstractFormElement)
            ) {
                $formRenderables[$renderable->getIdentifier()] = $renderable;
            }
        }

        return $formRenderables;
    }

    /**
     * Generates and returns the csv content by a given formPersistenceIdentifier
     *
     * @param string $formPersistenceIdentifier
     * @param bool $filtered
     * @return string
     * @throws InvalidQueryException
     * @throws RenderingException
     */
    protected function getCsvContent(string $formPersistenceIdentifier, bool $filtered = false): string
    {
        $csvDelimiter = $this->extConfUtility->getConfig('csvDelimiter') ?? ',';
        $csvContent = [];

        $formResults = $this->formResultRepository->findByFormPersistenceIdentifier($formPersistenceIdentifier);
        $formDefinition = $this->getFormDefinitionObject($formPersistenceIdentifier, true);
        $formRenderables = $this->getFormRenderables($formDefinition);

        $displayActiveFieldsOnly = $this->extConfUtility->getConfig('displayActiveFieldsOnly') ?? false;

        if ($filtered === true || $displayActiveFieldsOnly === true) {
            /** @var AbstractFormElement $renderable */
            foreach ($formRenderables as $i => $renderable) {
                $renderingOptions = $renderable->getRenderingOptions();

                if ($filtered && isset($renderingOptions['listView']) && $renderingOptions['listView'] !== 1) {
                    unset($formRenderables[$i]);
                }

                if ($displayActiveFieldsOnly && $renderingOptions['deleted'] === 1) {
                    unset($formRenderables[$i]);
                }
            }
        }

        $this->eventDispatcher->dispatch(
            new FormResultDownloadCSVActionEvent(
                $formPersistenceIdentifier,
                $formResults,
                $formDefinition,
                $formRenderables
            )
        );

        $header = [
            self::CSV_ENCLOSURE . $this->getLanguageService()->sL('LLL:EXT:form_to_database/Resources/Private/Language/locallang_be.xlf:show.crdate') . self::CSV_ENCLOSURE
        ];

        /** @var AbstractFormElement $renderable */
        foreach ($formRenderables as $renderable) {
            $header[] = self::CSV_ENCLOSURE . $renderable->getLabel() . self::CSV_ENCLOSURE;
        }
        $csvContent[] = implode($csvDelimiter, $header);

        /** @var FormResult $formResult */
        foreach ($formResults as $i => $formResult) {
            $resultsArray = $formResult->getResultAsArray();
            $content = [
                self::CSV_ENCLOSURE . $formResult->getCrdate()->format(FormValueUtility::getDateFormat() . ' ' . FormValueUtility::getTimeFormat()) . self::CSV_ENCLOSURE
            ];
            foreach ($formRenderables as $renderable) {
                $fieldValue = $resultsArray[$renderable->getIdentifier()] ?? '';
                $convertedFieldValue = FormValueUtility::convertFormValue($renderable, $fieldValue,
                    FormValueUtility::OUTPUT_TYPE_CSV);
                $cleanFieldValue = trim(str_replace(self::CSV_ENCLOSURE, '\\' . self::CSV_ENCLOSURE,
                    $convertedFieldValue));
                $content[] = self::CSV_ENCLOSURE . $cleanFieldValue . self::CSV_ENCLOSURE;
            }
            $csvContent[] = implode($csvDelimiter, $content);
        }

        return implode(self::CSV_LINEBREAK, $csvContent);
    }

    /**
     * Creates and returns the csv filename by a given formPersistenceIdentifier
     *
     * @param string $formPersistenceIdentifier
     * @return string
     * @throws Exception
     */
    protected function getCsvFilename(string $formPersistenceIdentifier): string
    {
        /** @var LocalDriver $localDriver */
        $localDriver = GeneralUtility::makeInstance(LocalDriver::class);
        $dateTime = new DateTime(
            'now',
            FormValueUtility::getValidTimezone((string)$GLOBALS['TYPO3_CONF_VARS']['SYS']['phpTimeZone'])
        );
        $filename = $dateTime->format(FormValueUtility::getDateFormat() . ' ' . FormValueUtility::getTimeFormat());
        $filename .= '_' . preg_replace('/\.form\.yaml$/', '', basename($formPersistenceIdentifier)) . '.csv';
        return $localDriver->sanitizeFileName($filename);
    }

    protected function getSingleResultProperties($uid, array $excludeFields = []): array
    {
        $formResult = $this->formResultRepository->findByUid($uid);
        $formPersistenceIdentifier = $formResult->getFormPersistenceIdentifier();

        $formDefinition = $this->getFormDefinitionObject($formResult->getFormPersistenceIdentifier(), false);
        $formRenderables = $this->getFormRenderables($formDefinition);

        $this->eventDispatcher->dispatch(
            new FormResultSingleResultActionEvent(
                $formPersistenceIdentifier,
                $formResult,
                $formDefinition,
                $formRenderables,
                $this->request->getArgument('action')
            )
        );

        $variables = [
            'formResult' => $formResult,
            'formDefinition' => $formDefinition,
            'formRenderables' => $formRenderables,
            'formPersistenceIdentifier' => $formPersistenceIdentifier,
            'hasPdfAbility' => class_exists(Mpdf::class)
        ];

        if(count($excludeFields) > 0) {
            $variables['formDefinitionAll'] = $this->getFormDefinitionObject($formResult->getFormPersistenceIdentifier(), false, $excludeFields);
            $variables['formRenderablesAll'] = $this->getFormRenderables($variables['formDefinitionAll'], false);
        }

        $this->view->assignMultiple($variables);
        $this->assignDefaults();

        return $variables;
    }

    /**
     * Assigns the default variables
     */
    protected function assignDefaults(): void
    {
        $this->view->assignMultiple([
            'dateFormat' => FormValueUtility::getDateFormat(),
            'timeFormat' => FormValueUtility::getTimeFormat(),
            'extConf' => $this->extConfUtility->getFullConfig()
        ]);
    }

    /**
     * Register document header buttons
     *
     * @param string|null $formPersistenceIdentifier
     * @param bool $showCsvDownload
     * @noinspection PhpUndefinedMethodInspection
     */
    protected function registerDocheaderButtons(
        string $formPersistenceIdentifier = null,
        bool $showCsvDownload = false
    ): void {
        /** @var ButtonBar $buttonBar */
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $currentRequest = $this->request;
        $moduleName = $currentRequest->getPluginName();
        $getVars = $this->request->getArguments();

        if ($this->request->getControllerActionName() === 'show') {
            $backFormButton = $buttonBar->makeLinkButton()
                ->setHref($this->getModuleUrl('web_FormToDatabaseFormresults'))
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:form_to_database/Resources/Private/Language/locallang_be.xlf:show.buttons.backlink'))
                ->setShowLabelText(true)
                ->setIcon($this->iconFactory->getIcon('actions-view-go-back', Icon::SIZE_SMALL));
            $buttonBar->addButton($backFormButton, ButtonBar::BUTTON_POSITION_LEFT);

            if ($formPersistenceIdentifier !== null && $showCsvDownload === true) {
                $urlParameters = [
                    'formPersistenceIdentifier' => $formPersistenceIdentifier,
                ];

                // Full list download-button
                $downloadCsvFormButton = $buttonBar->makeLinkButton()
                    ->setHref($this->uriBuilder->uriFor('downloadCsv', $urlParameters))
                    ->setTitle($this->getLanguageService()->sL('LLL:EXT:form_to_database/Resources/Private/Language/locallang_be.xlf:show.buttons.download_csv'))
                    ->setShowLabelText(true)
                    ->setIcon($this->iconFactory->getIcon('actions-download',
                        Icon::SIZE_SMALL));
                $buttonBar->addButton($downloadCsvFormButton, ButtonBar::BUTTON_POSITION_LEFT, 2);

                // Filtered list download-button
                $urlParameters['filtered'] = true;
                $downloadCsvFormButton = $buttonBar->makeLinkButton()
                    ->setHref($this->uriBuilder->uriFor('downloadCsv', $urlParameters))
                    ->setTitle($this->getLanguageService()->sL('LLL:EXT:form_to_database/Resources/Private/Language/locallang_be.xlf:show.buttons.download_csv_filtered'))
                    ->setShowLabelText(true)
                    ->setIcon($this->iconFactory->getIcon('actions-download', Icon::SIZE_SMALL));
                $buttonBar->addButton($downloadCsvFormButton, ButtonBar::BUTTON_POSITION_LEFT, 2);
            }
        }

        if ($this->request->getControllerActionName() === 'result') {
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);

            $backFormButton = $buttonBar->makeLinkButton()
                ->setHref($uriBuilder->buildUriFromRoute(
                    'web_FormToDatabaseFormresults',
                    [
                        'formPersistenceIdentifier' => $formPersistenceIdentifier,
                        'action' => 'show',
                        'controller' => 'FormResults',
                    ]
                ))
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:form_to_database/Resources/Private/Language/locallang_be.xlf:show.buttons.backlinkResults'))
                ->setShowLabelText(true)
                ->setIcon($this->iconFactory->getIcon('actions-view-go-back', Icon::SIZE_SMALL));
            $buttonBar->addButton($backFormButton, ButtonBar::BUTTON_POSITION_LEFT);
        }

        // Reload title
        $reloadTitle = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.reload');
        $reloadButton = $buttonBar->makeLinkButton()
            ->setHref(GeneralUtility::getIndpEnv('REQUEST_URI'))
            ->setTitle($reloadTitle)
            ->setIcon($this->iconFactory->getIcon('actions-refresh', Icon::SIZE_SMALL));
        $buttonBar->addButton($reloadButton, ButtonBar::BUTTON_POSITION_RIGHT);

        // Shortcut
        $mayMakeShortcut = $this->getBackendUser()->mayMakeShortcut();
        if ($mayMakeShortcut) {
            $extensionName = $currentRequest->getControllerExtensionName();
            if (count($getVars) === 0) {
                $modulePrefix = strtolower('tx_' . $extensionName . '_' . $moduleName);
                $getVars = ['id', 'route', $modulePrefix];
            }
            $shortcutButton = $buttonBar->makeShortcutButton()
                ->setRouteIdentifier($moduleName)
                ->setDisplayName($this->getLanguageService()->sL('LLL:EXT:form/Resources/Private/Language/Database.xlf:module.shortcut_name'))
                ->setArguments($getVars);
            $buttonBar->addButton($shortcutButton, ButtonBar::BUTTON_POSITION_RIGHT);
        }
    }
}
