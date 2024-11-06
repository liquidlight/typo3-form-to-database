<?php

declare(strict_types=1);

namespace Lavitto\FormToDatabase\Test\Functional\Service;

use Lavitto\FormToDatabase\Service\FormResultDatabaseService;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FormResultDatabaseServiceTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'lavitto/form-to-database',
    ];

    protected array $coreExtensionsToLoad = [
        'typo3/cms-form',
    ];

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function noFormsInSystemReturnsEmpty(): void
    {
        $service = GeneralUtility::makeInstance(FormResultDatabaseService::class);

        $forms = $service->getAllFormResultsForPersistenceIdentifier();

        self::assertIsArray($forms);
        self::assertEmpty($forms);
    }
}
