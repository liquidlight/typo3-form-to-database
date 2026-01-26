<?php
/**
 * This file is part of the "form_to_database" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

/**
 * Extension Manager/Repository config file for ext "form_to_database".
 */

/** @noinspection PhpUndefinedVariableInspection */
$EM_CONF[$_EXTKEY] = [
    'title' => 'Form to Database',
    'description' => 'Extends the TYPO3 form with a very simple database finisher, to save the form-results in the database.',
    'category' => 'frontend',
    'constraints' => [
        'depends' => [
            'typo3' => '9.5.0-10.4.99',
            'form' => '9.5.0-10.4.99'
        ],
        'conflicts' => [],
        'suggests' => []
    ],
    'autoload' => [
        'psr-4' => [
            'LiquidLight\\FormToDatabase\\' => 'Classes'
        ],
    ],
    'state' => 'beta',
    'createDirs' => '',
    'author' => 'Liquid Light',
    'author_email' => 'info@liquidlight.co.uk',
    'version' => '2.3.0'
];
