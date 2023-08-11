<?php
/**
 * This file is part of the "form_to_database" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Lavitto\FormToDatabase\Domain\Repository;

use DateInterval;
use DateTime;
use Exception;
use Lavitto\FormToDatabase\Utility\FormValueUtility;
use PDO;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\QueryGenerator;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Class FormResultRepository
 *
 * @package Lavitto\FormToDatabase\Domain\Repository
 */
class FormResultRepository extends Repository
{

    /**
     * Sort by tstamp desc
     *
     * @var array
     */
    protected $defaultOrderings = [
        'tstamp' => QueryInterface::ORDER_DESCENDING
    ];

    /**
     * Ignore storage pid
     */
    public function initializeObject(): void
    {
        /** @var Typo3QuerySettings $defaultQuerySettings */
        $defaultQuerySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);
        $defaultQuerySettings->setRespectStoragePage(false);
        $this->setDefaultQuerySettings($defaultQuerySettings);
    }

    /**
     * Gets all results by form definition
     *
     * @param string $formPersistenceIdentifier
     * @return QueryResultInterface
     * @throws InvalidQueryException
     */
    public function findByFormPersistenceIdentifierAndStartAndDate(string $formPersistenceIdentifier, int $startDate, int $endDate): QueryResultInterface
    {
        return $this->createQueryByFormPersistenceIdentifierAndStartAndDate($formPersistenceIdentifier, $startDate, $endDate)->execute();
    }

    /**
     * Counts all results by form definition
     *
     * @param string $formPersistenceIdentifier
     * @return int
     * @throws InvalidQueryException
     */
    public function countByFormPersistenceIdentifier(string $formPersistenceIdentifier): int
    {
        return $this->createQueryByFormPersistenceIdentifier($formPersistenceIdentifier)->count();
    }

    /**
     * Creates a query with by formPersistenceIdentifier
     *
     * @param string $formPersistenceIdentifier
     * @return QueryInterface
     * @throws InvalidQueryException
     */
    protected function createQueryByFormPersistenceIdentifierAndStartAndDate(string $formPersistenceIdentifier, int $startDate, int $endDate): QueryInterface
    {
        $query = $this->createQuery();
        $webMounts = $this->getWebMounts();
        if (empty($webMounts) === false) {
            $siteIdentifiers = $this->getSiteIdentifiersFromRootPids($webMounts);
            $pluginUids = $this->getPluginUids($webMounts);
            $orConditions = [];
            // Include result if user has access to the plugin which the result originates
            if ($pluginUids) {
                $orConditions[] = $query->in('formPluginUid', $pluginUids);
            }
            // Include result if user has root access to site
            if ($siteIdentifiers) {
                $orConditions[] = $query->in('siteIdentifier', $siteIdentifiers);
            }
            // Includes result if result is old (those created before new identifying fields)
            $orConditions[] = $query->logicalAnd([
                $query->equals('siteIdentifier', ''),
                $query->equals('pid', 0)
            ]);
            // Include result always if user is admin
            if ($GLOBALS['BE_USER']->isAdmin()) {
                $orConditions[] = $query->greaterThan('uid', 0);
            }

            $query->matching(
                $query->logicalAnd([
                    $query->equals('formPersistenceIdentifier', $formPersistenceIdentifier),
                    $query->lessThanOrEqual('tstamp', $endDate),
                    $query->greaterThanOrEqual('tstamp', $startDate),
                    $query->logicalOr($orConditions)
                ])
            );
        }
        return $query;
    }

    /**
     * Get webMounts of BE User
     *
     * @return array
     */
    protected function getWebMounts(): array
    {
        return $GLOBALS['BE_USER']->returnWebmounts();
    }

    /**
     * Gets the plugin uids
     *
     * @param array $webMounts
     * @return array
     */
    protected function getPluginUids(array $webMounts): array
    {
        $pids = $this->getTreePids($webMounts);
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();
        $result = $queryBuilder
            ->select('uid')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->in('pid', $pids),
                $queryBuilder->expr()->eq('CType',
                    $queryBuilder->createNamedParameter('form_formframework', PDO::PARAM_STR))
            )
            ->execute()->fetchAll();
        return array_column($result, 'uid');
    }

    /**
     * Get all pids which user can access
     *
     * @param array $webMounts
     * @return array
     */
    protected function getTreePids(array $webMounts): array
    {
        $pidsArray = [];
        if ($webMounts !== null) {
            $depth = 99;
            $pidsArray = [];
            /** @var QueryGenerator $queryGenerator */
            $queryGenerator = GeneralUtility::makeInstance(QueryGenerator::class);
            foreach ($webMounts as $webMount) {
                $childPids = $queryGenerator->getTreeList($webMount, $depth, 0, 1); //Will be a string like 1,2,3
                foreach (GeneralUtility::intExplode(',', $childPids, true) as $childPid) {
                    $pidsArray[] = $childPid;
                }
            }
        }
        return array_unique($pidsArray);
    }

    /**
     * Get SiteIdentifiers from Root Pids
     *
     * @param array $webMounts
     * @return array
     */
    protected function getSiteIdentifiersFromRootPids(array $webMounts): array
    {
        $siteIdentifiers = [];
        if ($webMounts) {
            // find site identifiers from mountpoints
            /** @var SiteFinder $siteMatcher */
            $siteMatcher = GeneralUtility::makeInstance(SiteFinder::class);
            foreach ($webMounts as $webMount) {
                try {
                    $site = $siteMatcher->getSiteByRootPageId((int)$webMount);
                    $siteIdentifiers[] = $site->getIdentifier();
                } catch (SiteNotFoundException $exception) {
                }
            }
        }
        return $siteIdentifiers;
    }

    /**
     * Returns all form results were older than "maxAge" (days)
     *
     * @param int $maxAge
     * @return QueryResultInterface
     * @throws InvalidQueryException
     * @throws Exception
     */
    public function findByMaxAge(int $maxAge): QueryResultInterface
    {
        $dateInterval = DateInterval::createFromDateString($maxAge . ' days');
        $maxDate = new DateTime('now',
            FormValueUtility::getValidTimezone((string)$GLOBALS['TYPO3_CONF_VARS']['SYS']['phpTimeZone']));
        $maxDate->sub($dateInterval);
        $query = $this->createQuery();
        $query->matching($query->lessThan('tstamp', $maxDate));
        return $query->execute();
    }

    public function getOldestDate(string $formPersistenceIdentifier): int
    {
        $tablename = 'tx_formtodatabase_domain_model_formresult';
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tablename);
        return (int)$queryBuilder
            ->select('tstamp')
            ->from($tablename)
            ->where(
                $queryBuilder->expr()->eq('form_persistence_identifier', $queryBuilder->createNamedParameter($formPersistenceIdentifier))
            )
            ->orderBy('tstamp', QueryInterface::ORDER_ASCENDING)
            ->setMaxResults(1)
            ->execute()
            ->fetchOne();
    }
}
