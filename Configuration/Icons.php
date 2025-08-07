<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    // Icon identifier
    'tx-formtodatabase' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:form_to_database/Resources/Public/Icons/Extension.svg',
    ],
    'actions-print' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:form_to_database/Resources/Public/Icons/action-print.svg',
    ],
];
