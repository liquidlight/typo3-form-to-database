<?php

declare(strict_types=1);

namespace LiquidLight\FormToDatabase\Test\Functional\Finisher;

use LiquidLight\FormToDatabase\Test\Functional\SiteBasedTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\StreamFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FormToDatabaseFinisherTest extends FunctionalTestCase
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

    protected array $pathsToProvideInTestInstance = [
        'typo3conf/ext/form_to_database/Tests/Functional/Fixtures/fileadmin/' => 'fileadmin',
    ];

    protected array $coreExtensionsToLoad = [
        'typo3/cms-form',
        'typo3/cms-fluid',
        'typo3/cms-frontend',
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
            'form-to-database-test',
            'typo3-form-to-database',
        ];
        $this->writeSiteConfiguration(
            'test-base',
            $site,
            ['en' => $this->buildLanguageConfiguration('en', '/')]
        );
    }

    /**
     * @return \Generator<string, array{
     *     name: non-empty-string,
     *     expectedResultFile: non-empty-string
     * }>
     */
    public static function saveDoneDataProvider(): \Generator
    {
        yield 'Normal name saved correctly' => [
            'name' => 'John Doe',
            'expectedResultFile' => __DIR__ . '/Fixtures/Results/JohnDoeResult.csv',
        ];

        yield 'Name with umlauts saved correctly' => [
            'name' => 'Ömer Üzgel',
            'expectedResultFile' => __DIR__ . '/Fixtures/Results/UmlautResult.csv',
        ];

        yield 'Name with special characters saved correctly' => [
            'name' => 'Tuğçe Çelik',
            'expectedResultFile' => __DIR__ . '/Fixtures/Results/SpecialCharactersResult.csv',
        ];
    }

    #[Test]
    #[DataProvider('saveDoneDataProvider')]
    public function namesAreSavedCorrectToDatabase(string $name, string $expectedResultFile): void
    {
        $queryParams = [
            'tx_form_formframework' => [
                'action' => 'perform',
                'controller' => 'FormFrontend',
            ],
            'id' => 1,
        ];
        $cacheHashCalculator = GeneralUtility::makeInstance(CacheHashCalculator::class);
        $queryParams['cHash'] = $cacheHashCalculator->generateForParameters(http_build_query($queryParams));

        unset($queryParams['id']);

        $body = [
            'tx_form_formframework' => [
                'testform-1' => [
                    '__state' => 'TzozOToiVFlQTzNcQ01TXEZvcm1cRG9tYWluXFJ1bnRpbWVcRm9ybVN0YXRlIjoyOntzOjI1OiIAKgBsYXN0RGlzcGxheWVkUGFnZUluZGV4IjtpOjA7czoxMzoiACoAZm9ybVZhbHVlcyI7YTowOnt9fQ==47a5df7cc91032d809f04ce767df252ca8bdcd67',
                    '__session' => '8618e8dfe31948295237ebf3d69e5e6ff86a121e|5bd206863401e9ff977ea5b658bb4405b5ddd765',
                    '__trustedProperties' => '{&quot;testform-1&quot;:{&quot;name&quot;:1,&quot;vR6Q2szYwtVrnZ81GqyN&quot;:1,&quot;__currentPage&quot;:1}}92cb9ee7e8aa1332939a703408f10973f2ab8307',
                    '__currentPage' => 1,
                    'vR6Q2szYwtVrnZ81GqyN' => '',
                    'name' => $name,
                ],
            ],
        ];

        $streamFactory = GeneralUtility::makeInstance(StreamFactory::class);

        $internalRequest = (new InternalRequest('https://localhost/'))
            ->withQueryParams($queryParams)
            ->withMethod('POST')
            ->withBody($streamFactory->createStream(http_build_query($body)));

        $result = $this->executeFrontendSubRequest($internalRequest);

        $status = $result->getStatusCode();

        self::assertEquals(200, $status);
        self::assertCSVDataSet($expectedResultFile);
    }
}
