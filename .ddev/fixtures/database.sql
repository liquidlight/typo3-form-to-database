-- Demo data for manual QA of the form_to_database extension.
-- Imported by `ddev init-typo3` after a fresh TYPO3 setup + extension:setup.
-- The file-based counterpart to "demo-db-form" is `files/form_definitions/demo-file-form.form.yaml`,
-- copied into fileadmin by the same command.

INSERT INTO form_definition (uid, pid, deleted, identifier, label, configuration)
VALUES (
    1,
    0,
    0,
    'demo-db-form',
    'Demo Database Form',
    '{"type":"Form","identifier":"demo-db-form","label":"Demo Database Form","prototypeName":"standard","renderingOptions":{"submitButtonLabel":"Submit","fieldState":{"name":{"identifier":"name","label":"Full name","type":"Text","renderingOptions":{"deleted":0}}}},"finishers":[{"identifier":"FormToDatabase"}],"renderables":[{"type":"Page","identifier":"page-1","label":"Step","renderingOptions":{"previousButtonLabel":"Previous step","nextButtonLabel":"Next step"},"renderables":[{"type":"Text","identifier":"name","label":"Full name","defaultValue":"","properties":{"fluidAdditionalAttributes":{"autocomplete":"name"}}}]}]}'
);

-- A handful of "current" results (visible on page 1 without any sorting), plus a bulk
-- of older, timestamp-staggered results per form so pagination (20 results/page on the
-- "show" action) and date-sorting both have something to page/sort through.
INSERT INTO tx_formtodatabase_domain_model_formresult
    (pid, tstamp, crdate, form_persistence_identifier, form_identifier, site_identifier, form_plugin_uid, result)
VALUES
    (0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), '1:/form_definitions/demo-file-form.form.yaml', 'demo-file-form', '', 0, '{"name":"Jane Doe"}'),
    (0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), '1:/form_definitions/demo-file-form.form.yaml', 'demo-file-form', '', 0, '{"name":"John Smith"}'),
    (0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), '1', 'demo-db-form', '', 0, '{"name":"Alice Example"}'),
    (0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), '1', 'demo-db-form', '', 0, '{"name":"Bob Example"}'),
    (0, UNIX_TIMESTAMP() - 102, UNIX_TIMESTAMP() - 102, '1:/form_definitions/demo-file-form.form.yaml', 'demo-file-form', '', 0, '{"name":"Sam Miller"}'),
    (0, UNIX_TIMESTAMP() - 87403, UNIX_TIMESTAMP() - 87403, '1:/form_definitions/demo-file-form.form.yaml', 'demo-file-form', '', 0, '{"name":"Victor Walker"}'),
    (0, UNIX_TIMESTAMP() - 175816, UNIX_TIMESTAMP() - 175816, '1:/form_definitions/demo-file-form.form.yaml', 'demo-file-form', '', 0, '{"name":"Frank Brown"}'),
    (0, UNIX_TIMESTAMP() - 259556, UNIX_TIMESTAMP() - 259556, '1:/form_definitions/demo-file-form.form.yaml', 'demo-file-form', '', 0, '{"name":"Bob Baker"}'),
    (0, UNIX_TIMESTAMP() - 345730, UNIX_TIMESTAMP() - 345730, '1:/form_definitions/demo-file-form.form.yaml', 'demo-file-form', '', 0, '{"name":"Quinn Hill"}'),
    (0, UNIX_TIMESTAMP() - 432895, UNIX_TIMESTAMP() - 432895, '1:/form_definitions/demo-file-form.form.yaml', 'demo-file-form', '', 0, '{"name":"Jane Example"}'),
    (0, UNIX_TIMESTAMP() - 520865, UNIX_TIMESTAMP() - 520865, '1:/form_definitions/demo-file-form.form.yaml', 'demo-file-form', '', 0, '{"name":"Frank Adams"}'),
    (0, UNIX_TIMESTAMP() - 605614, UNIX_TIMESTAMP() - 605614, '1:/form_definitions/demo-file-form.form.yaml', 'demo-file-form', '', 0, '{"name":"Jane Baker"}'),
    (0, UNIX_TIMESTAMP() - 692918, UNIX_TIMESTAMP() - 692918, '1:/form_definitions/demo-file-form.form.yaml', 'demo-file-form', '', 0, '{"name":"Uma Baker"}'),
    (0, UNIX_TIMESTAMP() - 780013, UNIX_TIMESTAMP() - 780013, '1:/form_definitions/demo-file-form.form.yaml', 'demo-file-form', '', 0, '{"name":"Frank Scott"}'),
    (0, UNIX_TIMESTAMP() - 867108, UNIX_TIMESTAMP() - 867108, '1:/form_definitions/demo-file-form.form.yaml', 'demo-file-form', '', 0, '{"name":"Grace Doe"}'),
    (0, UNIX_TIMESTAMP() - 953259, UNIX_TIMESTAMP() - 953259, '1:/form_definitions/demo-file-form.form.yaml', 'demo-file-form', '', 0, '{"name":"Xander Davis"}'),
    (0, UNIX_TIMESTAMP() - 1037938, UNIX_TIMESTAMP() - 1037938, '1:/form_definitions/demo-file-form.form.yaml', 'demo-file-form', '', 0, '{"name":"Liam King"}'),
    (0, UNIX_TIMESTAMP() - 1126327, UNIX_TIMESTAMP() - 1126327, '1:/form_definitions/demo-file-form.form.yaml', 'demo-file-form', '', 0, '{"name":"Carol Clark"}'),
    (0, UNIX_TIMESTAMP() - 1209979, UNIX_TIMESTAMP() - 1209979, '1:/form_definitions/demo-file-form.form.yaml', 'demo-file-form', '', 0, '{"name":"Ivy Miller"}'),
    (0, UNIX_TIMESTAMP() - 1297470, UNIX_TIMESTAMP() - 1297470, '1:/form_definitions/demo-file-form.form.yaml', 'demo-file-form', '', 0, '{"name":"Karen Miller"}'),
    (0, UNIX_TIMESTAMP() - 1384872, UNIX_TIMESTAMP() - 1384872, '1:/form_definitions/demo-file-form.form.yaml', 'demo-file-form', '', 0, '{"name":"Zoe Wright"}'),
    (0, UNIX_TIMESTAMP() - 1471788, UNIX_TIMESTAMP() - 1471788, '1:/form_definitions/demo-file-form.form.yaml', 'demo-file-form', '', 0, '{"name":"Grace Smith"}'),
    (0, UNIX_TIMESTAMP() - 1555711, UNIX_TIMESTAMP() - 1555711, '1:/form_definitions/demo-file-form.form.yaml', 'demo-file-form', '', 0, '{"name":"Mia Baker"}'),
    (0, UNIX_TIMESTAMP() - 1643861, UNIX_TIMESTAMP() - 1643861, '1:/form_definitions/demo-file-form.form.yaml', 'demo-file-form', '', 0, '{"name":"Karen Example"}'),
    (0, UNIX_TIMESTAMP() - 1731529, UNIX_TIMESTAMP() - 1731529, '1:/form_definitions/demo-file-form.form.yaml', 'demo-file-form', '', 0, '{"name":"Henry Carter"}'),
    (0, UNIX_TIMESTAMP() - 1815187, UNIX_TIMESTAMP() - 1815187, '1:/form_definitions/demo-file-form.form.yaml', 'demo-file-form', '', 0, '{"name":"Jack Nelson"}'),
    (0, UNIX_TIMESTAMP() - 1900987, UNIX_TIMESTAMP() - 1900987, '1:/form_definitions/demo-file-form.form.yaml', 'demo-file-form', '', 0, '{"name":"Uma Example"}'),
    (0, UNIX_TIMESTAMP() - 1990366, UNIX_TIMESTAMP() - 1990366, '1:/form_definitions/demo-file-form.form.yaml', 'demo-file-form', '', 0, '{"name":"Tara Lewis"}'),
    (0, UNIX_TIMESTAMP() - 2077103, UNIX_TIMESTAMP() - 2077103, '1:/form_definitions/demo-file-form.form.yaml', 'demo-file-form', '', 0, '{"name":"Henry Example"}'),
    (0, UNIX_TIMESTAMP() - 1556, UNIX_TIMESTAMP() - 1556, '1', 'demo-db-form', '', 0, '{"name":"Frank Miller"}'),
    (0, UNIX_TIMESTAMP() - 89003, UNIX_TIMESTAMP() - 89003, '1', 'demo-db-form', '', 0, '{"name":"Grace Scott"}'),
    (0, UNIX_TIMESTAMP() - 173466, UNIX_TIMESTAMP() - 173466, '1', 'demo-db-form', '', 0, '{"name":"Yara Wright"}'),
    (0, UNIX_TIMESTAMP() - 260058, UNIX_TIMESTAMP() - 260058, '1', 'demo-db-form', '', 0, '{"name":"Jack Wright"}'),
    (0, UNIX_TIMESTAMP() - 348474, UNIX_TIMESTAMP() - 348474, '1', 'demo-db-form', '', 0, '{"name":"Tara Walker"}'),
    (0, UNIX_TIMESTAMP() - 434495, UNIX_TIMESTAMP() - 434495, '1', 'demo-db-form', '', 0, '{"name":"Tara Example"}'),
    (0, UNIX_TIMESTAMP() - 520587, UNIX_TIMESTAMP() - 520587, '1', 'demo-db-form', '', 0, '{"name":"Sam Davis"}'),
    (0, UNIX_TIMESTAMP() - 605469, UNIX_TIMESTAMP() - 605469, '1', 'demo-db-form', '', 0, '{"name":"Victor Lewis"}'),
    (0, UNIX_TIMESTAMP() - 692305, UNIX_TIMESTAMP() - 692305, '1', 'demo-db-form', '', 0, '{"name":"Mia Lopez"}'),
    (0, UNIX_TIMESTAMP() - 778499, UNIX_TIMESTAMP() - 778499, '1', 'demo-db-form', '', 0, '{"name":"Sam Baker"}'),
    (0, UNIX_TIMESTAMP() - 867452, UNIX_TIMESTAMP() - 867452, '1', 'demo-db-form', '', 0, '{"name":"Tara King"}'),
    (0, UNIX_TIMESTAMP() - 951338, UNIX_TIMESTAMP() - 951338, '1', 'demo-db-form', '', 0, '{"name":"Wendy Smith"}'),
    (0, UNIX_TIMESTAMP() - 1040097, UNIX_TIMESTAMP() - 1040097, '1', 'demo-db-form', '', 0, '{"name":"Yara Smith"}'),
    (0, UNIX_TIMESTAMP() - 1124296, UNIX_TIMESTAMP() - 1124296, '1', 'demo-db-form', '', 0, '{"name":"Ivy Lopez"}'),
    (0, UNIX_TIMESTAMP() - 1211923, UNIX_TIMESTAMP() - 1211923, '1', 'demo-db-form', '', 0, '{"name":"Alice Clark"}'),
    (0, UNIX_TIMESTAMP() - 1296870, UNIX_TIMESTAMP() - 1296870, '1', 'demo-db-form', '', 0, '{"name":"Uma King"}'),
    (0, UNIX_TIMESTAMP() - 1384020, UNIX_TIMESTAMP() - 1384020, '1', 'demo-db-form', '', 0, '{"name":"Sam Green"}'),
    (0, UNIX_TIMESTAMP() - 1469385, UNIX_TIMESTAMP() - 1469385, '1', 'demo-db-form', '', 0, '{"name":"Sam Scott"}'),
    (0, UNIX_TIMESTAMP() - 1556210, UNIX_TIMESTAMP() - 1556210, '1', 'demo-db-form', '', 0, '{"name":"Grace Brown"}'),
    (0, UNIX_TIMESTAMP() - 1643807, UNIX_TIMESTAMP() - 1643807, '1', 'demo-db-form', '', 0, '{"name":"Victor Baker"}'),
    (0, UNIX_TIMESTAMP() - 1729754, UNIX_TIMESTAMP() - 1729754, '1', 'demo-db-form', '', 0, '{"name":"Grace Nelson"}'),
    (0, UNIX_TIMESTAMP() - 1815882, UNIX_TIMESTAMP() - 1815882, '1', 'demo-db-form', '', 0, '{"name":"Quinn Lopez"}'),
    (0, UNIX_TIMESTAMP() - 1902887, UNIX_TIMESTAMP() - 1902887, '1', 'demo-db-form', '', 0, '{"name":"Frank Brown"}'),
    (0, UNIX_TIMESTAMP() - 1990295, UNIX_TIMESTAMP() - 1990295, '1', 'demo-db-form', '', 0, '{"name":"Noah Example"}'),
    (0, UNIX_TIMESTAMP() - 2074226, UNIX_TIMESTAMP() - 2074226, '1', 'demo-db-form', '', 0, '{"name":"John Miller"}');

-- Embed both demo forms on the frontend root page (pid=1, created by `typo3 setup`), so
-- the FormToDatabase finisher can be exercised end-to-end by actually submitting a form,
-- not just by inspecting the seeded results above.
INSERT INTO tt_content (pid, tstamp, crdate, colPos, CType, header, pi_flexform)
VALUES
    (1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'form_formframework', 'Demo Database Form (DB storage)', '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
<T3FlexForms>
    <data>
        <sheet index="sDEF">
            <language index="lDEF">
                <field index="settings.persistenceIdentifier">
                    <value index="vDEF">1</value>
                </field>
            </language>
        </sheet>
    </data>
</T3FlexForms>
'),
    (1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'form_formframework', 'Demo File Form (file storage)', '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
<T3FlexForms>
    <data>
        <sheet index="sDEF">
            <language index="lDEF">
                <field index="settings.persistenceIdentifier">
                    <value index="vDEF">1:/form_definitions/demo-file-form.form.yaml</value>
                </field>
            </language>
        </sheet>
    </data>
</T3FlexForms>
');
