<?php

declare(strict_types=1);

namespace Lavitto\FormToDatabase\Test\Functional\Command;

use Lavitto\FormToDatabase\Command\DeleteFormResultCommand;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class DeleteFormResultCommandTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'lavitto/typo3-form-to-database',
    ];

    protected array $coreExtensionsToLoad = [
        'typo3/cms-form',
    ];

    protected array $pathsToProvideInTestInstance = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/deleteCommandDataset.csv');

        // needed for Extbase configuration
        // if not set, Configuration will not get loaded
        // and the repository can't create a query
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest('/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
    }

    /**
     * @throws \Exception
     */
    #[Test]
    public function formsGetDeletedAfterDefaultDays(): void
    {
        $tester = new CommandTester(new DeleteFormResultCommand());
        $tester->execute([]);

        self::assertEquals(0, $tester->getStatusCode());
        self::assertCSVDataSet(__DIR__ . '/Fixtures/deleteCommandResult.csv');
    }
}
