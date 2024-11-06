<?php

/**
 * This file is part of the "form_to_database" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Lavitto\FormToDatabase\Service;

use Doctrine\DBAL\Exception;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class FormResultDatabaseService
 */
final class FormResultDatabaseService
{
    /**
     * Returns an array with all form definition persistenceIdentifiers as keys and the number of form results as values.
     *
     * @return array<string, int|string>
     */
    public function getAllFormResultsForPersistenceIdentifier(): array
    {
        $items = [];
        foreach ($this->getAllFormResults() as $result) {
            $items[$result['identifier']] = $result['numberOfResults'];
        }
        return $items;
    }

    /**
     * Returns an array with the number of results for all available forms
     *
     * @return array<array-key, array<string, mixed>>
     * @throws Exception
     */
    protected function getAllFormResults(): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_refindex');
        return $queryBuilder
            ->select('form_persistence_identifier as identifier')
            ->addSelectLiteral('COUNT(' . $queryBuilder->quoteIdentifier('form_persistence_identifier') . ') as "numberOfResults"')
            ->from('tx_formtodatabase_domain_model_formresult')
            ->groupBy('form_persistence_identifier')
            ->executeQuery()
            ->fetchAllAssociative();
    }
}
