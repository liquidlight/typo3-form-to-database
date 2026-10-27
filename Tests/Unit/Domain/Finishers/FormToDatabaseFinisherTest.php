<?php

declare(strict_types=1);

namespace LiquidLight\FormToDatabase\Test\Unit\Domain\Finishers;

use LiquidLight\FormToDatabase\Domain\Finishers\FormToDatabaseFinisher;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\FormElements\FormElementInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FormToDatabaseFinisherTest extends UnitTestCase
{
    #[Test]
    public function repeatableContainerRowsAreSavedWithTheirNestedIdentifiers(): void
    {
        $subject = $this->createSubject(['name', 'repeat.0.name', 'repeat.1.name']);

        $result = $this->getFormFieldValues($subject, [
            'name' => 'Jane Doe',
            'repeat' => [
                0 => ['name' => 'First'],
                1 => ['name' => 'Second'],
            ],
        ]);

        self::assertEquals(
            [
                'name' => 'Jane Doe',
                'repeat.0.name' => 'First',
                'repeat.1.name' => 'Second',
            ],
            $result
        );
    }

    #[Test]
    public function excludedFieldTypesAreNotSaved(): void
    {
        $subject = $this->createSubject(['name', 'text-1'], ['text-1' => 'StaticText']);

        $result = $this->getFormFieldValues($subject, [
            'name' => 'Jane Doe',
            'text-1' => 'Some static text',
        ]);

        self::assertEquals(['name' => 'Jane Doe'], $result);
    }

    /**
     * @param list<string> $identifiers Element identifiers known to the form definition
     * @param array<string, string> $types Element types by identifier, defaults to "Text"
     */
    private function createSubject(array $identifiers, array $types = []): FormToDatabaseFinisher
    {
        $formDefinition = $this->createMock(FormDefinition::class);
        $formDefinition->method('getElementByIdentifier')->willReturnCallback(
            function (string $identifier) use ($identifiers, $types): ?FormElementInterface {
                if (!in_array($identifier, $identifiers, true)) {
                    return null;
                }

                $element = $this->createMock(FormElementInterface::class);
                $element->method('getType')->willReturn($types[$identifier] ?? 'Text');

                return $element;
            }
        );

        $subject = (new \ReflectionClass(FormToDatabaseFinisher::class))->newInstanceWithoutConstructor();
        (new \ReflectionProperty(FormToDatabaseFinisher::class, 'formDefinition'))->setValue($subject, $formDefinition);

        return $subject;
    }

    /**
     * @param array<array-key, mixed> $fields
     * @return array<string, mixed>
     */
    private function getFormFieldValues(FormToDatabaseFinisher $subject, array $fields): array
    {
        return (new \ReflectionMethod(FormToDatabaseFinisher::class, 'getFormFieldValues'))->invoke($subject, $fields);
    }
}
