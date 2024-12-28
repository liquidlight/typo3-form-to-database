<?php

declare(strict_types=1);

namespace Lavitto\FormToDatabase\Test\Functional\Utility;

use Lavitto\FormToDatabase\Controller\FormResultsController;
use Lavitto\FormToDatabase\Test\Functional\SiteBasedTestTrait;
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
        'lavitto/typo3-form-to-database',
        __DIR__ . '/../Fixtures/test_extension',
    ];

    protected array $coreExtensionsToLoad = [
        'typo3/cms-form',
        'typo3/cms-fluid-styled-content',
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
            'lavitto/form-to-database-test',
            'lavitto/form-to-database-save-to-extension-test',
            'typo3/form',
            'lavitto/typo3-form-to-database',
            'typo3/fluid-styled-content',
            'typo3/fluid-styled-content-css',
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
        $arguments = [
            'tx_form_web_formformbuilder' => [
                'formPersistenceIdentifier' => 'EXT:test_extension/Resources/Private/Forms/EditTest.form.yaml',
                'formDefinition' => <<<JSON
{
  "renderingOptions": {
    "submitButtonLabel": "Send"
  },
  "type": "Form",
  "identifier": "test_1",
  "label": "Test",
  "prototypeName": "standard",
  "_orig_type": {
    "value": "Form",
    "hmac": "5523d003f248c06ad3ce1afb4981f6c9e1a5539a"
  },
  "_orig_identifier": {
    "value": "test_1",
    "hmac": "593cab97b367ec7f834eb1e70c13426dc87d8308"
  },
  "_orig_label": {
    "value": "EditTest",
    "hmac": "f2edab114f9516d20e885a5d4bec89a4c389e879"
  },
  "_orig_prototypeName": {
    "value": "standard",
    "hmac": "ef3657fc2c4069bdead5617df4547971f18adb5e"
  },
  "renderables": [
    {
      "renderingOptions": {
        "previousButtonLabel": "Previous+page",
        "nextButtonLabel": "Next+step"
      },
      "type": "Page",
      "identifier": "page-1",
      "label": "Step",
      "_orig_type": {
        "value": "Page",
        "hmac": "4a01e965fdbd7e3c29bbf4320098e6dedf31b68d"
      },
      "_orig_identifier": {
        "value": "page-1",
        "hmac": "3b6081061be9d91949d4ffe92709250b30fc2e76"
      },
      "_orig_label": {
        "value": "Step",
        "hmac": "d7904475511b4075212435369962ecc3630d4613"
      }
    }
  ]
}
JSON,
            ],
        ];
        $extbaseRequestParameters = (new ExtbaseRequestParameters(FormResultsController::class))
            ->setPluginName('web_FormFormbuilder')
            ->setArguments($arguments);
        $route = (new Route('web_FormFormbuilder', []));
        $serverRequest = (new ServerRequest('https://localhost/typo3/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('extbase', $extbaseRequestParameters)
            ->withAttribute('route', $route)
            ->withQueryParams([
                'tx_form_web_formformbuilder' => [
                    'action' => 'saveForm',
                    'controller' => 'FormEditor',
                ],
            ])
            ->withParsedBody($arguments)
            ->withMethod('POST');
        $GLOBALS['TYPO3_REQUEST'] = $serverRequest;
        $GLOBALS['BE_USER'] = GeneralUtility::makeInstance(BackendUserAuthentication::class);
        $GLOBALS['BE_USER']->start($serverRequest);
        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageServiceFactory::class)
            ->create('en-GB');
        // a:2:{s:26:"formProtectionSessionToken";s:64:"98c617f1e24f5a786324a1889ff9a88cd4c3b8f52fdf7ffd8f07e3baac658d4c";s:29:"extFormProtectionSessionToken";s:64:"b4824343fbe6ccab0e3f15cd7239a0cc43e64d8836ffab7f51a6fe26defce2f1";}
        $GLOBALS['BE_USER']->setAndSaveSessionData('extFormProtectionSessionToken', '3685b029fb42de8c45ce01afa864c5656f295f32f7060c67baaf079c5509eb62');
        //$GLOBALS['BE_USER']->setAndSaveSessionData('formProtectionSessionToken', '98c617f1e24f5a786324a1889ff9a88cd4c3b8f52fdf7ffd8f07e3baac658d4c');

        $extbaseRequest = (new Request($serverRequest))
            ->withControllerActionName('saveForm')
            ->withControllerName('FormEditor')
            ->withArguments($arguments['tx_form_web_formformbuilder']);

        $formEditorController = GeneralUtility::makeInstance(FormEditorController::class);
        $response = $formEditorController->processRequest($extbaseRequest);

        self::assertEquals(200, $response->getStatusCode());
    }
}
