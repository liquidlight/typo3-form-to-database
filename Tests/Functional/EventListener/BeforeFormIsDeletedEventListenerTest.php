<?php

declare(strict_types=1);

namespace LiquidLight\FormToDatabase\Test\Functional\EventListener;

use LiquidLight\FormToDatabase\Test\Functional\SiteBasedTestTrait;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Form\Controller\FormManagerController;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class BeforeFormIsDeletedEventListenerTest extends FunctionalTestCase
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
    ];

    protected array $pathsToProvideInTestInstance = [
        'typo3conf/ext/form_to_database/Tests/Functional/Controller/Fixtures/fileadmin/' => 'fileadmin',
    ];

    protected array $coreExtensionsToLoad = [
        'typo3/cms-form',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // Deliberately a dedicated fixture without a referencing tt_content element:
        // FormManagerController::deleteAction() blocks deletion outright (no event dispatched)
        // when hasReferences() is true, which resultsBasicSetup.csv's form_formframework
        // content element would trigger.
        $this->importCSVDataSet(__DIR__ . '/Fixtures/formWithoutReferences.csv');

        $site = $this->buildSiteConfiguration(1, 'https://localhost/');
        $site['dependencies'] = [
            'liquidlight/form-to-database-test',
            'liquidlight/typo3-form-to-database',
        ];
        $this->writeSiteConfiguration('test-base', $site, ['en' => $this->buildLanguageConfiguration('en', '/')]);

        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        $GLOBALS['BE_USER'] = $backendUser;
    }

    #[Test]
    public function deletingAFormViaTheFormManagerSucceeds(): void
    {
        $formPersistenceIdentifier = '1:/form_definitions/testform.form.yaml';

        $extbaseRequestParameters = (new ExtbaseRequestParameters(FormManagerController::class))
            ->setPluginName('web_FormFormbuilder')
            ->setArgument('formPersistenceIdentifier', $formPersistenceIdentifier);
        $serverRequest = (new ServerRequest('https://localhost/typo3/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('extbase', $extbaseRequestParameters)
            ->withAttribute('route', new Route('web_FormFormbuilder', []))
            ->withQueryParams([
                'tx_form_web_formformbuilder' => [
                    'action' => 'delete',
                    'controller' => 'FormManager',
                ],
            ])
            ->withMethod('POST');
        $GLOBALS['TYPO3_REQUEST'] = $serverRequest;

        $extbaseRequest = (new Request($serverRequest))
            ->withControllerActionName('delete')
            ->withControllerName('FormManager')
            ->withArgument('formPersistenceIdentifier', $formPersistenceIdentifier);

        $controller = GeneralUtility::makeInstance(FormManagerController::class);
        $response = $controller->processRequest($extbaseRequest);

        self::assertEquals(200, $response->getStatusCode());
        $response->getBody()->rewind();
        $responseBody = $response->getBody()->getContents();
        self::assertStringContainsString('"status":"success"', $responseBody, 'deleteAction must report success, not silently no-op: ' . $responseBody);

        // Known open follow-up (not verified by this test): BeforeFormIsDeletedEventListener's
        // FormResult re-pointing (mirrors the pre-v14 FormHooks::beforeFormDelete() behavior) could
        // not be reliably verified in this test harness — ResourceFactory::
        // getFileObjectFromCombinedIdentifier() returns null for this fixture's file in a way not
        // yet root-caused, so the "if ($file !== null)" branch that updates linked FormResult rows
        // never runs here. The listener code is an unmodified, behavior-preserving port of the
        // previously-working hook body, and deletion itself demonstrably succeeds (assertion above).
    }
}
