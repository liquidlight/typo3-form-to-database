<?php

declare(strict_types=1);

namespace LiquidLight\FormToDatabase\Test\Functional\Utility;

use LiquidLight\FormToDatabase\Controller\FormResultsController;
use LiquidLight\FormToDatabase\Test\Functional\SiteBasedTestTrait;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Form\Controller\FormEditorController;
use TYPO3\CMS\Form\Controller\FormManagerController;
use TYPO3\CMS\Form\Domain\Configuration\FormDefinitionConversionService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class UniqueFieldHandlerTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'en' => [
            'id' => 0,
            'title' => 'TYPO3 Form Save To Database',
            'locale' => 'en_GB',
            'flag' => 'en',
        ],
    ];

    protected array $testExtensionsToLoad = [
        'liquidlight/typo3-form-to-database',
        __DIR__ . '/../Fixtures/test_extension',
    ];

    protected array $coreExtensionsToLoad = [
        'typo3/cms-form',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/finisherBasicSetup.csv');

        $site = $this->buildSiteConfiguration(
            1,
            'https://localhost/'
        );

        $site['dependencies'] = [
            'liquidlight/form-to-database-test',
            'liquidlight/form-to-database-save-to-extension-test',
            'liquidlight/typo3-form-to-database',
        ];
        $this->writeSiteConfiguration(
            'test-base',
            $site,
            ['en' => $this->buildLanguageConfiguration('en', '/')]
        );

        // remove possible old form saves
        GeneralUtility::rmdir(GeneralUtility::getFileAbsFileName('EXT:test_extension/Resources/Private/Forms/saveTest.form.yaml'));
    }

    #[Test]
    public function createEmptyIsPossibleWithinExtensionPath(): void
    {
        $arguments = [
            'tx_form_web_formformbuilder' => [
                'savePath' => 'EXT:test_extension/Resources/Private/Forms/',
                'formName' => 'SaveTest',
                'prototypeName' => 'standard',
                'templatePath' => 'EXT:form/Resources/Private/Backend/Templates/FormEditor/Yaml/NewForms/BlankForm.yaml',
            ],
        ];
        $extbaseRequestParameters = (new ExtbaseRequestParameters(FormResultsController::class))
            ->setPluginName('web_FormFormbuilder');
        $route = (new Route('web_FormFormbuilder', []));
        $serverRequest = (new ServerRequest('https://localhost/typo3/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('extbase', $extbaseRequestParameters)
            ->withAttribute('route', $route)
            ->withQueryParams([
                'tx_form_web_formformbuilder' => [
                    'action' => 'create',
                    'controller' => 'FormManager',
                ],
            ])
            ->withParsedBody($arguments)
            ->withMethod('POST');
        $GLOBALS['TYPO3_REQUEST'] = $serverRequest;
        $GLOBALS['BE_USER'] = GeneralUtility::makeInstance(BackendUserAuthentication::class);

        $extbaseRequest = (new Request($serverRequest))
            ->withControllerActionName('create')
            ->withControllerName('FormManager')
            ->withArguments($arguments['tx_form_web_formformbuilder']);

        $formManagerController = GeneralUtility::makeInstance(FormManagerController::class);
        $response = $formManagerController->processRequest($extbaseRequest);

        self::assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function saveToExtensionIsPossibleWithHook(): void
    {
        $formPersistenceIdentifier = 'EXT:test_extension/Resources/Private/Forms/EditTest.form.yaml';

        $serverRequest = (new ServerRequest('https://localhost/typo3/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', new Route('web_FormFormbuilder', []))
            ->withMethod('POST');

        $GLOBALS['TYPO3_REQUEST'] = $serverRequest;
        $GLOBALS['BE_USER'] = GeneralUtility::makeInstance(BackendUserAuthentication::class);
        $GLOBALS['BE_USER']->start($serverRequest);
        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageServiceFactory::class)->create('en-GB');

        // Generate valid HMAC data and store the session token under the correct key
        $baseFormDefinition = [
            'renderingOptions' => ['submitButtonLabel' => 'Send'],
            'type' => 'Form',
            'identifier' => 'test_1',
            'label' => 'Test',
            'prototypeName' => 'standard',
            'renderables' => [
                [
                    'renderingOptions' => [
                        'previousButtonLabel' => 'Previous+page',
                        'nextButtonLabel' => 'Next+step',
                    ],
                    'type' => 'Page',
                    'identifier' => 'page-1',
                    'label' => 'Step',
                ],
            ],
        ];

        $formDefinitionWithHmac = GeneralUtility::makeInstance(FormDefinitionConversionService::class)
            ->addHmacData($baseFormDefinition, $formPersistenceIdentifier);

        $arguments = [
            'tx_form_web_formformbuilder' => [
                'formPersistenceIdentifier' => $formPersistenceIdentifier,
                'formDefinition' => json_encode($formDefinitionWithHmac),
            ],
        ];

        $extbaseRequestParameters = (new ExtbaseRequestParameters(FormResultsController::class))
            ->setPluginName('web_FormFormbuilder')
            ->setArguments($arguments);

        $serverRequest = $serverRequest
            ->withAttribute('extbase', $extbaseRequestParameters)
            ->withQueryParams([
                'tx_form_web_formformbuilder' => [
                    'action' => 'saveForm',
                    'controller' => 'FormEditor',
                ],
            ])
            ->withParsedBody($arguments);

        $GLOBALS['TYPO3_REQUEST'] = $serverRequest;

        $extbaseRequest = (new Request($serverRequest))
            ->withControllerActionName('saveForm')
            ->withControllerName('FormEditor')
            ->withArguments($arguments['tx_form_web_formformbuilder']);

        $formEditorController = GeneralUtility::makeInstance(FormEditorController::class);
        $response = $formEditorController->processRequest($extbaseRequest);

        self::assertEquals(200, $response->getStatusCode());
    }
}
