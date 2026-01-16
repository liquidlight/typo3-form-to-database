<?php

declare(strict_types=1);

namespace LiquidLight\FormToDatabase\Test\Functional\Service;

use LiquidLight\FormToDatabase\Service\FormResultDatabaseService;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * @see FormResultDatabaseService
 */
final class FormResultDatabaseServiceTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3-form-to-database',
    ];

    protected array $coreExtensionsToLoad = [
        'typo3/cms-form',
    ];

    #[Test]
    public function noFormsInSystemReturnsEmptyArray(): void
    {
        $service = GeneralUtility::makeInstance(FormResultDatabaseService::class);

        $forms = $service->getAllFormResultsForPersistenceIdentifier();

        self::assertIsArray($forms);
        self::assertEmpty($forms);
    }
}
