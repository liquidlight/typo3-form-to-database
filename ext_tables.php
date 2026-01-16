<?php /** @noinspection PhpFullyQualifiedNameUsageInspection */
/**
 * This file is part of the "form_to_database" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

defined('TYPO3_MODE') or die();

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
    'FormToDatabase',
    'web',
    'formresults',
    'after:FormFormbuilder',
    [
        \LiquidLight\FormToDatabase\Controller\FormResultsController::class => 'index, show, result, downloadResultPdf, downloadCsv, deleteFormResult, updateItemListSelect, unDeleteFormDefinition',
    ],
    [
        'access' => 'user,group',
        'icon' => 'EXT:form_to_database/Resources/Public/Icons/Extension.svg',
        'labels' => 'LLL:EXT:form_to_database/Resources/Private/Language/locallang_mod.xlf',
        'navigationComponentId' => '',
        'inheritNavigationComponentFromMainModule' => false
    ]
);
