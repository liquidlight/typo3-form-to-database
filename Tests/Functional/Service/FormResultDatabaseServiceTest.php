<?php

declare(strict_types=1);

namespace Lavitto\FormToDatabase\Test\Functional\Service;

use Lavitto\FormToDatabase\Service\FormResultDatabaseService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * @see FormResultDatabaseService
 */
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
    public function noFormsInSystemReturnsEmptyArray(): void
    {
        $service = GeneralUtility::makeInstance(FormResultDatabaseService::class);

        $forms = $service->getAllFormResultsForPersistenceIdentifier();

        self::assertIsArray($forms);
        self::assertEmpty($forms);
    }
}
