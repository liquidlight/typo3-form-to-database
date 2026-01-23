<?php

declare(strict_types=1);

namespace LiquidLight\FormToDatabase\Test\Functional\Command;

use LiquidLight\FormToDatabase\Command\DeleteFormResultCommand;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class DeleteFormResultCommandTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3-form-to-database',
    ];

    protected array $coreExtensionsToLoad = [
        'typo3/cms-form',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/deleteCommandDataset.csv');
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
