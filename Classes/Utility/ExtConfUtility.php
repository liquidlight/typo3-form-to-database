<?php
/**
 * This file is part of the "form_to_database" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Lavitto\FormToDatabase\Utility;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class FormValueUtility
 */
class ExtConfUtility implements SingletonInterface
{
    /**
     * @var array
     */
    protected $extConf;

    /**
     * @var array
     */
    protected $defaultExtConf = [
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
        $this->extConf = array_merge($this->defaultExtConf, $extConf);
        $this->validateExtConf();
    }

    /**
     * Returns the full configuration
     *
     * @return array
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
     */
    public function getConfig(string $key)
    {
        return $this->extConf[$key];
    }

    /**
     * Validates the configuration
     */
    protected function validateExtConf(): void
    {
        foreach ($this->defaultExtConf as $field => $value) {
            if (is_bool($value) === true) {
                $this->extConf[$field] = (bool)$this->extConf[$field];
            } elseif (is_int($value) === true) {
                $this->extConf[$field] = (int)$this->extConf[$field];
            } elseif (is_float($value) === true) {
                $this->extConf[$field] = (float)$this->extConf[$field];
            } elseif (is_string($value) === true) {
                $this->extConf[$field] = trim((string)$this->extConf[$field]);
            }
        }
    }
}
