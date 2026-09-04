<?php

declare(strict_types=1);

/**
 * This file is part of the "form_to_database" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace LiquidLight\FormToDatabase\Utility;

use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Class FormPersistenceIdentifierUtility
 */
class FormPersistenceIdentifierUtility
{
    /**
     * Database-stored forms (TYPO3\CMS\Form\Storage\DatabaseStorageAdapter) are identified by a
     * numeric persistence identifier (the form_definition record's uid), unlike file- or
     * extension-stored forms, which use a combined FAL identifier or an EXT: path.
     */
    public static function isDatabaseStored(string $persistenceIdentifier): bool
    {
        return MathUtility::canBeInterpretedAsInteger($persistenceIdentifier);
    }
}
