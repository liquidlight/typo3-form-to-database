<?php

declare(strict_types=1);

return [
    'web_FormToDatabaseFormresults' => [
        'parent' => 'content',
        'position' => ['after' => 'web_FormFormbuilder'],
        'access' => 'user',
        'workspaces' => '*',
        'icon'   => 'EXT:form_to_database/Resources/Public/Icons/module-form-to-database.svg',
        'path' => '/module/web/FormToDatabaseFormresults',
        'labels' => 'LLL:EXT:form_to_database/Resources/Private/Language/locallang_mod.xlf',
        'extensionName' => 'FormToDatabase',
        'controllerActions' => [
            \LiquidLight\FormToDatabase\Controller\FormResultsController::class => [
                'index', 'show', 'result', 'downloadResultPdf', 'downloadCsv', 'deleteFormResult', 'itemListSelect', 'updateItemListSelect', 'unDeleteFormDefinition', 'deleteAllFormResult',
            ],
        ],
        'inheritNavigationComponentFromMainModule' => false,
    ],
];
