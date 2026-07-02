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
            'liquidlight/form-to-database-test',
            'liquidlight/typo3-form-to-database',
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

    /**
     * Renders the form via a plain GET request and scrapes the current __state/__session/
     * __trustedProperties/honeypot hidden field values out of the response HTML, instead of
     * hardcoding pre-computed HMAC tokens. TYPO3 v14 made the HMAC algorithm for __state and
     * __session explicit (HashAlgo::SHA3_256, see TYPO3\CMS\Form\Domain\Runtime\FormRuntime and
     * FormSession), which invalidated the previously hardcoded v13 tokens — scraping them from a
     * live render is resilient to future internal algorithm/format changes too.
     *
     * @return array<string, string>
     */
    private function getRenderedFormHiddenFields(): array
    {
        $result = $this->executeFrontendSubRequest(new InternalRequest('https://localhost/'));
        self::assertEquals(200, $result->getStatusCode());
        $html = (string)$result->getBody();

        preg_match_all(
            '/<input[^>]*name="tx_form_formframework\[testform-1\]\[([^\]]+)\]"[^>]*value="([^"]*)"/',
            $html,
            $matches,
            PREG_SET_ORDER
        );
        self::assertNotEmpty($matches, 'Expected to find hidden tx_form_formframework fields in the rendered form output');

        $fields = [];
        foreach ($matches as $match) {
            $fields[$match[1]] = html_entity_decode($match[2], ENT_QUOTES | ENT_HTML5);
        }

        return $fields;
    }

    #[Test]
    #[DataProvider('saveDoneDataProvider')]
    public function namesAreSavedCorrectToDatabase(string $name, string $expectedResultFile): void
    {
        $formFields = $this->getRenderedFormHiddenFields();
        $formFields['name'] = $name;
        // This fixture form has a single page; submitting with currentPage advanced past
        // the last page index is what triggers finisher execution (matches this form's
        // page-count semantics, not a value read from the HMAC-signed __trustedProperties).
        $formFields['__currentPage'] = 1;

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
                'testform-1' => $formFields,
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
