<?php
/**
 * This file is part of the "form_to_database" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

(static function (): void {
    ExtensionManagementUtility::addTypoScriptSetup('
        plugin.tx_form.settings.yamlConfigurations {
          1560425499 = EXT:form_to_database/Configuration/Yaml/BaseSetup.yaml
        }

        module.tx_form.settings.yamlConfigurations {
          1560425499 = EXT:form_to_database/Configuration/Yaml/BaseSetup.yaml
          1560425500 = EXT:form_to_database/Configuration/Yaml/FormEditorSetup.yaml
        }
    ');
})();
