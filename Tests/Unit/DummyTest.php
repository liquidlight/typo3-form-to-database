<?php

declare(strict_types=1);

namespace LiquidLight\FormToDatabase\Test\Unit;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class DummyTest extends UnitTestCase
{
    #[Test]
    public function systemIsLoadable(): void
    {
        self::assertTrue(true);
    }
}
