<?php
/**
 * This file is part of the "form_to_database" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Lavitto\FormToDatabase\Utility;

use Lavitto\FormToDatabase\Exception\ExtensionConfigurationKeyNotFoundException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Class FormValueUtility
 */
class ExtConfUtility implements SingletonInterface
{
    /**
     * @var array<string, mixed>
     */
    protected array $extConf;

    /**
     * @var array{
     *     hideLocationInList: bool,
     *     csvDelimiter: string,
     *     csvOnlyFilenameOfUploadFields: bool
     * }
     */
    protected const DEFAULT_EXT_CONF = [
        'hideLocationInList' => false,
        'csvDelimiter' => ',',
        'csvOnlyFilenameOfUploadFields' => false,
        'displayActiveFieldsOnly' => false,
    ];

    /**
     * Initialize the ExtConfUtility
     */
    public function initializeObject(): void
    {
        $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('form_to_database');
        $this->extConf = array_merge(self::DEFAULT_EXT_CONF, $extConf);
        $this->validateExtConf();
    }

    /**
     * Returns the full configuration
     *
     * @return array<array-key, mixed>
     */
    public function getFullConfig(): array
    {
        return $this->extConf;
    }

    /**
     * Gets a configuration
     *
     * @param string $key
     * @return mixed
     * @throws ExtensionConfigurationKeyNotFoundException
     */
    public function getConfig(string $key): mixed
    {
        if (!array_key_exists($key, $this->extConf)) {
            throw new ExtensionConfigurationKeyNotFoundException(
                sprintf('The value for configuration key "%s" was not found', $key),
                1730895228747
            );
        }
        return $this->extConf[$key];
    }

    /**
     * Validates the configuration
     */
    protected function validateExtConf(): void
    {
        /**
         * @var string $field
         * @var int|string|float|bool $value
         */
        foreach ($this->extConf as $field => $value) {
            $this->extConf[$field] = match(true) {
                is_bool($value) => (bool)$this->extConf[$field],
                MathUtility::canBeInterpretedAsInteger($value) => (int)$this->extConf[$field],
                MathUtility::canBeInterpretedAsFloat($value) => (float)$this->extConf[$field],
                default => trim((string)$this->extConf[$field]),
            };
        }
    }
}
