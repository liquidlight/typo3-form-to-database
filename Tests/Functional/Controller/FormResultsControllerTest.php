<?php

declare(strict_types=1);

namespace LiquidLight\FormToDatabase\Test\Functional\Controller;

use LiquidLight\FormToDatabase\Controller\FormResultsController;
use LiquidLight\FormToDatabase\Test\Functional\SiteBasedTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FormResultsControllerTest extends FunctionalTestCase
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
        'typo3-form-to-database',
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

        $this->importCSVDataSet(__DIR__ . '/Fixtures/resultsBasicSetup.csv');

        $site = $this->buildSiteConfiguration(
            1,
            'https://localhost/'
        );

        $site['dependencies'] = [
            'form-to-database-test',
            'typo3-form-to-database',
        ];
        $this->writeSiteConfiguration(
            'test-base',
            $site,
            ['en' => $this->buildLanguageConfiguration('en', '/')]
        );

        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    /**
     * @return \Generator<string, array{
     *     form: string,
     *     expectedBody: string,
     *     expectedContentType: string,
     *     expectedStatus: int,
     *     expectedContentLength: string
     * }>
     */
    public static function exportDataProvider(): \Generator
    {
        yield 'Basic Export' => [
            'form' => '1:/form_definitions/testform.form.yaml',
            'expectedBody' => '﻿"Date/Time","Full name","Company","Street","ZIP","City","Country"
"2023-11-07 16:37","John Doe","Company A","Main Street 1","12345","New York","USA"
"2023-11-07 16:38","Tuğçe Çelik","Şirket B","Ana Cadde 2","34000","Istanbul","Türkiye"
"2023-11-07 16:54","Ömer Üzgel","Firma C","Hauptstraße 3","D-54321","Köln","Deutschland"',
            'expectedContentType' => 'text/csv; charset=UTF-8',
            'expectedStatus' => 200,
            'expectedContentLength' => '336',
        ];
    }

    /**
     * @return \Generator<string, array{
     *     form: string,
     *     expectedBody: string,
     *     expectedContentType: string,
     *     expectedStatus: int,
     *     expectedContentLength: string
     * }>
     */
    public static function filteredExportDataProvider(): \Generator
    {
        yield 'Filtered Export' => [
            'form' => '1:/form_definitions/testform.form.yaml',
            'expectedBody' => '﻿"Date/Time","Full name","ZIP","City"
"2023-11-07 16:37","John Doe","12345","New York"
"2023-11-07 16:38","Tuğçe Çelik","34000","Istanbul"
"2023-11-07 16:54","Ömer Üzgel","D-54321","Köln"',
            'expectedContentType' => 'text/csv; charset=UTF-8',
            'expectedStatus' => 200,
            'expectedContentLength' => '195',
        ];
    }

    #[Test]
    #[DataProvider('exportDataProvider')]
    public function resultFullExportReturnsCsv(
        string $form,
        string $expectedBody,
        string $expectedContentType,
        int $expectedStatus,
        string $expectedContentLength
    ): void {
        $extbaseRequestParameters = (new ExtbaseRequestParameters(FormResultsController::class))
            ->setPluginName('web_FormToDatabaseFormresults')
            ->setArgument('formPersistenceIdentifier', $form);
        $route = (new Route('web_FormToDatabaseFormresults', []));
        $serverRequest = (new ServerRequest('https://localhost/typo3/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('extbase', $extbaseRequestParameters)
            ->withAttribute('route', $route)
            ->withQueryParams([
                'formPersistenceIdentifier' => $form,
                'tx_formtodatabase_formresults' => [
                    'action' => 'downloadCsv',
                    'controller' => 'FormResults',
                ],
            ]);
        $GLOBALS['TYPO3_REQUEST'] = $serverRequest;

        $extbaseRequest = (new Request($serverRequest))
            ->withControllerActionName('downloadCsv')
            ->withAttribute('formPersistenceIdentifier', $form);

        /** @var FormResultsController $controller */
        $controller = GeneralUtility::makeInstance(FormResultsController::class);
        // For a complete load of the correct path, loading via processRequest is needed,
        // as only this call injects and sets up all requirements correct
        $response = $controller->processRequest($extbaseRequest);

        $response->getBody()->rewind();
        $body = $response->getBody()->getContents();
        $headers = $response->getHeaders();

        self::assertEquals($expectedStatus, $response->getStatusCode());
        self::assertIsArray($headers);
        self::assertArrayHasKey('Content-Type', $headers);
        self::assertIsArray($headers['Content-Type']);
        self::assertCount(1, $headers['Content-Type']);
        $contentType = array_pop($headers['Content-Type']);
        self::assertEquals($expectedContentType, $contentType);
        self::assertEquals($expectedBody, $body);
        self::assertArrayHasKey('Content-Length', $headers);
        self::assertIsArray($headers['Content-Length']);
        self::assertCount(1, $headers['Content-Length']);
        $contentLength = array_pop($headers['Content-Length']);
        self::assertEquals($expectedContentLength, $contentLength);
    }

    #[Test]
    #[DataProvider('filteredExportDataProvider')]
    public function resultFilteredExportReturnsCsv(
        string $form,
        string $expectedBody,
        string $expectedContentType,
        int $expectedStatus,
        string $expectedContentLength
    ): void {
        $backendUser = $this->getBackendUser();
        $backendUser->uc['tx_formtodatabase']['listViewStates']['testform']['name'] = 1;
        $backendUser->uc['tx_formtodatabase']['listViewStates']['testform']['company'] = 0;
        $backendUser->uc['tx_formtodatabase']['listViewStates']['testform']['street'] = 0;
        $backendUser->uc['tx_formtodatabase']['listViewStates']['testform']['zip'] = 1;
        $backendUser->uc['tx_formtodatabase']['listViewStates']['testform']['city'] = 1;
        $backendUser->uc['tx_formtodatabase']['listViewStates']['testform']['country'] = 0;
        $backendUser->writeUC();

        $extbaseRequestParameters = (new ExtbaseRequestParameters(FormResultsController::class))
            ->setPluginName('web_FormToDatabaseFormresults')
            ->setArgument('formPersistenceIdentifier', $form)
            ->setArgument('filtered', '1');
        $route = (new Route('web_FormToDatabaseFormresults', []));
        $serverRequest = (new ServerRequest('https://localhost/typo3/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('extbase', $extbaseRequestParameters)
            ->withAttribute('route', $route)
            ->withQueryParams([
                'formPersistenceIdentifier' => $form,
                'filtered' => '1',
                'tx_formtodatabase_formresults' => [
                    'action' => 'downloadCsv',
                    'controller' => 'FormResults',
                ],
            ]);
        $GLOBALS['TYPO3_REQUEST'] = $serverRequest;

        $extbaseRequest = (new Request($serverRequest))
            ->withControllerActionName('downloadCsv')
            ->withArgument('filtered', '1')
            ->withAttribute('formPersistenceIdentifier', $form);

        /** @var FormResultsController $controller */
        $controller = GeneralUtility::makeInstance(FormResultsController::class);
        // For a complete load of the correct path, loading via processRequest is needed,
        // as only this call injects and sets up all requirements correct
        $response = $controller->processRequest($extbaseRequest);

        $response->getBody()->rewind();
        $body = $response->getBody()->getContents();
        $headers = $response->getHeaders();

        self::assertEquals($expectedStatus, $response->getStatusCode());
        self::assertIsArray($headers);
        self::assertArrayHasKey('Content-Type', $headers);
        self::assertIsArray($headers['Content-Type']);
        self::assertCount(1, $headers['Content-Type']);
        $contentType = array_pop($headers['Content-Type']);
        self::assertEquals($expectedContentType, $contentType);
        self::assertEquals($expectedBody, $body);
        self::assertArrayHasKey('Content-Length', $headers);
        self::assertIsArray($headers['Content-Length']);
        self::assertCount(1, $headers['Content-Length']);
        $contentLength = array_pop($headers['Content-Length']);
        self::assertEquals($expectedContentLength, $contentLength);
    }

    private function getBackendUser(): \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
