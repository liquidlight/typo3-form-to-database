<?php

declare(strict_types=1);

/**
 * This file is part of the "form_to_database" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace LiquidLight\FormToDatabase\Domain\Repository;

use Doctrine\DBAL\Exception;
use LiquidLight\FormToDatabase\Domain\Model\FormResult;
use LiquidLight\FormToDatabase\Helpers\MiscHelper;
use LiquidLight\FormToDatabase\Utility\FormValueUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
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
 * @extends Repository<FormResult>
 */
class FormResultRepository extends Repository
{
    /**
     * Sort by tstamp desc
     *
     * @var array<non-empty-string, QueryInterface::ORDER_*>
     */
    protected $defaultOrderings = [
        'tstamp' => QueryInterface::ORDER_DESCENDING,
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
     * @return QueryResultInterface<FormResult>
     * @throws InvalidQueryException
     */
    public function findByFormPersistenceIdentifier(string $formPersistenceIdentifier): QueryResultInterface
    {
        return $this->createQueryByFormPersistenceIdentifier($formPersistenceIdentifier)->execute();
    }

    /**
     * Counts all results by form definition
     *
     * @throws InvalidQueryException
     */
    public function countByFormPersistenceIdentifier(string $formPersistenceIdentifier): int
    {
        return $this->createQueryByFormPersistenceIdentifier($formPersistenceIdentifier)->count();
    }

    /**
     * Creates a query with by formPersistenceIdentifier
     *
     * @return QueryInterface<FormResult>
     * @throws InvalidQueryException
     */
    protected function createQueryByFormPersistenceIdentifier(string $formPersistenceIdentifier): QueryInterface
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
            $orConditions[] = $query->logicalAnd(
                $query->equals('siteIdentifier', ''),
                $query->equals('pid', 0)
            );
            // Include result always if user is admin
            if ($GLOBALS['BE_USER']->isAdmin()) {
                $orConditions[] = $query->greaterThan('uid', 0);
            }

            $query->matching(
                $query->logicalAnd(
                    $query->equals('formPersistenceIdentifier', $formPersistenceIdentifier),
                    $query->logicalOr(...$orConditions)
                )
            );
        }
        return $query;
    }

    /**
     * Get webMounts of BE User
     *
     * @return int[]
     */
    protected function getWebMounts(): array
    {
        return $GLOBALS['BE_USER']->getWebmounts();
    }

    /**
     * Gets the plugin uids
     *
     * @param int[] $webMounts
     * @return int[]
     * @throws Exception
     */
    protected function getPluginUids(array $webMounts): array
    {
        $pids = $this->getTreePids($webMounts);
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();
        $result = $queryBuilder
            ->select('uid')
            ->from('tt_content')->where($queryBuilder->expr()->in('pid', $pids), $queryBuilder->expr()->eq(
                'CType',
                $queryBuilder->createNamedParameter('form_formframework', Connection::PARAM_STR)
            ))
            ->executeQuery();

        $pluginUids = [];

        while ($row = $result->fetchAssociative()) {
            $pluginUids[] = $row['uid'];
        }

        return $pluginUids;
    }

    /**
     * Get all pids which user can access
     *
     * @param int[] $webMounts
     * @return int[]
     * @throws Exception
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    protected function getTreePids(array $webMounts): array
    {
        $depth = 99;
        $pidsArray = [];
        foreach ($webMounts as $webMount) {
            $childPids = MiscHelper::getTreeList($webMount, $depth); //Will be a string like 1,2,3
            $pidsArray = array_merge(
                $pidsArray,
                GeneralUtility::intExplode(',', $childPids, true)
            );
        }

        return array_unique($pidsArray);
    }

    /**
     * Get SiteIdentifiers from Root Pids
     *
     * @param int[] $webMounts
     * @return string[]
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
                    $site = $siteMatcher->getSiteByRootPageId($webMount);
                    $siteIdentifiers[] = $site->getIdentifier();
                } catch (SiteNotFoundException) {
                }
            }
        }
        return $siteIdentifiers;
    }

    /**
     * Returns all form results were older than "maxAge" (days)
     *
     * @return QueryResultInterface<FormResult>
     * @throws InvalidQueryException
     * @throws \Exception
     */
    public function findByMaxAge(int $maxAge): QueryResultInterface
    {
        /** @var \DateInterval $dateInterval */
        $dateInterval = \DateInterval::createFromDateString($maxAge . ' days');
        $maxDate = new \DateTime(
            'now',
            FormValueUtility::getValidTimezone((string)$GLOBALS['TYPO3_CONF_VARS']['SYS']['phpTimeZone'])
        );
        $maxDate->sub($dateInterval);
        $query = $this->createQuery();
        $query->matching($query->lessThan('tstamp', $maxDate));
        return $query->execute();
    }
}
