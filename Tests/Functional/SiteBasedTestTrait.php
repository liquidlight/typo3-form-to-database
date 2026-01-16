<?php

declare(strict_types=1);

namespace LiquidLight\FormToDatabase\Test\Functional;

/**
 * Trait used for test classes that want to set up (= write) site configuration files.
 *
 * Mainly used when testing Site-related tests in Frontend requests.
 *
 * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
 * !!!NOTE!!! Be sure to set the LANGUAGE_PRESETS const in your class. !!!
 * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
 *
 * @todo This trait has been copied from the TYPO3 core tests, because the `typo3/testing-framework` do not contain
 *       this trait or similar feature set for now. This may change in the future, and this trait should then removed
 *       along with adopting tests to the introduced TF way to deal with this.
 *
 * Adopted from https://github.com/web-vision/deepltranslate-core/blob/main/Tests/Functional/Fixtures/Traits/SiteBasedTestTrait.php
 */

use TYPO3\CMS\Core\Configuration\SiteWriter;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Trait used for test classes that want to set up (= write) site configuration files.
 *
 * Mainly used when testing Site-related tests in Frontend requests.
 *
 * Be sure to set the LANGUAGE_PRESETS const in your class.
 */
trait SiteBasedTestTrait
{
    /**
     * @param array{
     *     rootPageId?: int,
     *      base?: string
     * } $site
     * @param array<string, array{
     *     languageId: int,
     *     title: string,
     *     navigationTitle: string,
     *     websiteTitle: string,
     *     base: string,
     *     locale: string,
     *     flag: string,
     *     fallbackType: string
     * }> $languages
     * @param array<string, string> $errorHandling
     */
    protected function writeSiteConfiguration(
        string $identifier,
        array $site = [],
        array $languages = [],
        array $errorHandling = []
    ): void {
        $configuration = $site;
        if (!empty($languages)) {
            $configuration['languages'] = $languages;
        }
        if (!empty($errorHandling)) {
            $configuration['errorHandling'] = $errorHandling;
        }
        $siteWriter = $this->get(SiteWriter::class);
        try {
            // ensure no previous site configuration influences the test
            GeneralUtility::rmdir($this->instancePath . '/typo3conf/sites/' . $identifier, true);
            $siteWriter->write($identifier, $configuration);
        } catch (\Exception $exception) {
            $this->markTestSkipped($exception->getMessage());
        }
    }

    /**
     * @return array{rootPageId: int, base: string}
     */
    protected function buildSiteConfiguration(
        int $rootPageId,
        string $base = ''
    ): array {
        return [
            'rootPageId' => $rootPageId,
            'base' => $base,
        ];
    }

    /**
     * @return array{
     *       languageId: int,
     *       title: string,
     *       navigationTitle: string,
     *       websiteTitle: string,
     *       base: string,
     *       locale: string,
     *       flag: string|'global'
     *   }
     */
    protected function buildDefaultLanguageConfiguration(
        string $identifier,
        string $base
    ): array {
        $configuration = $this->buildLanguageConfiguration($identifier, $base);
        $configuration['flag'] = 'global';
        unset($configuration['fallbackType'], $configuration['fallbacks']);
        return $configuration;
    }

    /**
     * @param array<array-key, mixed> $fallbackIdentifiers
     * @return array{
     *      languageId: int,
     *      title: string,
     *      navigationTitle: string,
     *      websiteTitle: string,
     *      base: string,
     *      locale: string,
     *      flag: string,
     *      fallbackType: string,
     *     fallbacks?: string
     *  }
     */
    protected function buildLanguageConfiguration(
        string $identifier,
        string $base,
        array $fallbackIdentifiers = [],
        ?string $fallbackType = null
    ): array {
        $preset = $this->resolveLanguagePreset($identifier);

        $configuration = [
            'languageId' => $preset['id'],
            'title' => $preset['title'],
            'navigationTitle' => $preset['title'],
            'websiteTitle' => $preset['websiteTitle'] ?? '',
            'base' => $base,
            'locale' => $preset['locale'],
            'flag' => $preset['iso'] ?? '',
            'fallbackType' => $fallbackType ?? (empty($fallbackIdentifiers) ? 'strict' : 'fallback'),
        ];

        if (!empty($fallbackIdentifiers)) {
            $fallbackIds = array_map(
                function (string $fallbackIdentifier) {
                    $preset = $this->resolveLanguagePreset($fallbackIdentifier);
                    return $preset['id'];
                },
                $fallbackIdentifiers
            );
            $configuration['fallbackType'] = $fallbackType ?? 'fallback';
            $configuration['fallbacks'] = implode(',', $fallbackIds);
        }

        return $configuration;
    }

    protected function resolveLanguagePreset(string $identifier): mixed
    {
        if (!isset(static::LANGUAGE_PRESETS[$identifier])) {
            throw new \LogicException(
                sprintf('Undefined preset identifier "%s"', $identifier),
                1533893665
            );
        }
        return static::LANGUAGE_PRESETS[$identifier];
    }
}
