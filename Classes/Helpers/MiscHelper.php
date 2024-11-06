<?php

/** @noinspection ALL */

namespace Lavitto\FormToDatabase\Helpers;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class MiscHelper
{
    /**
     * Get webmounts of BE User
     *
     * @return int[]
     */
    public static function getWebMounts(): array
    {
        return self::getBackendUser()->getWebmounts();
    }

    /**
     * Get SiteIdentifiers from Root Pids
     * @param int[] $webMounts
     * @return string[]
     */
    public static function getSiteIdentifiersFromRootPids(array $webMounts = []): array
    {
        $siteIdentifiers = [];
        if ($webMounts !== []) {
            //find site identifiers from mountpoints
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
     * @param int[] $webMounts
     * @return int[]
     */
    public static function getPluginUids(array $webMounts): array
    {
        $pids = self::getTreePids($webMounts);

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();
        $result = $queryBuilder
            ->select('uid')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->in('pid', $pids ?: [0]),
                $queryBuilder->expr()->eq(
                    'CType',
                    $queryBuilder->createNamedParameter('form_formframework', Connection::PARAM_STR)
                )
            )->executeQuery();

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
     */
    public static function getTreePids(array $webMounts = []): array
    {
        $childPidsArray = [];
        $depth = 99;
        foreach ($webMounts as $webMount) {
            $childPids = self::getTreeList($webMount, $depth); //Will be a string like 1,2,3
            $childPidsArray = array_merge(
                $childPidsArray,
                GeneralUtility::intExplode(',', $childPids, true)
            );
        }
        return array_unique($childPidsArray);
    }

    /**
     * Recursively fetch all descendants of a given page
     *
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public static function getTreeList(int $id, int $depth, int $begin = 0, string $permClause = ''): string
    {
        if ($id < 0) {
            $id = abs($id);
        }
        if ($begin === 0) {
            $theList = (string)$id;
        } else {
            $theList = '';
        }
        if ($id && $depth > 0) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
            $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            $queryBuilder->select('uid')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($id, Connection::PARAM_INT)),
                    $queryBuilder->expr()->eq('sys_language_uid', 0)
                )
                ->orderBy('uid');
            if ($permClause !== '') {
                $queryBuilder->andWhere(QueryHelper::stripLogicalOperatorPrefix($permClause));
            }
            $statement = $queryBuilder->executeQuery();
            while ($row = $statement->fetchAssociative()) {
                if ($begin <= 0) {
                    $theList .= ',' . $row['uid'];
                }
                if ($depth > 1) {
                    $theSubList = self::getTreeList($row['uid'], $depth - 1, $begin - 1, $permClause);
                    if (!empty($theList) && !empty($theSubList) && ($theSubList[0] !== ',')) {
                        $theList .= ',';
                    }
                    $theList .= $theSubList;
                }
            }
        }
        return $theList;
    }

    private static function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
