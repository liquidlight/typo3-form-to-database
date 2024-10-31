<?php

return [
    'web_FormToDatabaseFormresults' => [
        'parent' => 'web',
        'position' => ['after' => 'web_FormFormbuilder'],
        'access' => 'user',
        'workspaces' => '*',
        'icon'   => 'EXT:form_to_database/Resources/Public/Icons/Extension.svg',
        'path' => '/module/web/FormToDatabaseFormresults',
        'labels' => 'LLL:EXT:form_to_database/Resources/Private/Language/locallang_mod.xlf',
        'extensionName' => 'FormToDatabase',
        'controllerActions' => [
            \Lavitto\FormToDatabase\Controller\FormResultsController::class => [
                'index', 'show', 'result', 'downloadResultPdf', 'downloadCsv', 'deleteFormResult', 'updateItemListSelect', 'unDeleteFormDefinition',
            ],
        ],
        'inheritNavigationComponentFromMainModule' => false,
    ],
];
