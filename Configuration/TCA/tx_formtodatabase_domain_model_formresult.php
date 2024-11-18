<?php
/**
 * This file is part of the "form_to_database" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

defined('TYPO3') or die();

return [
    'ctrl' => [
        'title' => 'LLL:EXT:form_to_database/Resources/Private/Language/locallang_be.xlf:tx_formtodatabase_domain_model_formresult',
        'crdate' => 'crdate',
        'tstamp' => 'tstamp',
        'hideTable' => true,
        'typeicon_classes' => [
            'default' => 'tx-formtodatabase',
        ],
    ],
    'columns' => [
        'crdate' => [
            'label' => 'crdate',
            'config' => [
                'type' => 'passthrough'
            ]
        ],
        'tstamp' => [
            'label' => 'tstamp',
            'config' => [
                'type' => 'passthrough'
            ]
        ],
        'form_persistence_identifier' => [
            'label' => 'form_persistence_identifier',
            'config' => [
                'type' => 'passthrough'
            ]
        ],
        'form_identifier' => [
            'label' => 'form_identifier',
            'config' => [
                'type' => 'passthrough'
            ]
        ],
        'site_identifier' => [
            'label' => 'site_identifier',
            'config' => [
                'type' => 'passthrough'
            ]
        ],
        'form_plugin_uid' => [
            'label' => 'site_identifier',
            'config' => [
                'type' => 'passthrough'
            ]
        ],
        'result' => [
            'label' => 'result',
            'config' => [
                'type' => 'passthrough'
            ]
        ]
    ]
];
