<?php

declare(strict_types=1);

namespace Lavitto\FormToDatabase\Test\Functional\Controller;

use Generator;
use Lavitto\FormToDatabase\Controller\FormResultsController;
use Lavitto\FormToDatabase\Test\Functional\SiteBasedTestTrait;
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
        'lavitto/typo3-form-to-database',
    ];

    protected array $pathsToProvideInTestInstance = [
        'typo3conf/ext/form_to_database/Tests/Functional/Fixtures/fileadmin/' => 'fileadmin',
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
            'lavitto/form-to-database-test',
            'lavitto/typo3-form-to-database',
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
     * @return Generator<string, array{
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
            'expectedBody' => '﻿"Date/Time","Full name"
"2023-11-07 16:37","John Doe"
"2023-11-07 16:38","Tuğçe Çelik"
"2023-11-07 16:54","Ömer Üzgel"',
            'expectedContentType' => 'text/csv; charset=UTF-8',
            'expectedStatus' => 200,
            'expectedContentLength' => '126',
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
}
