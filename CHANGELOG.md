# Uncommitted

- [FEATURE] Add support for TYPO3 14
- [BREAKING] Drop support for TYPO3 13
- [FEATURE] Support forms stored in TYPO3 v14's new database storage (`form_definition`) alongside file-based storage — results, CSV/PDF export, and deletion/restoration all work for both
- [FEATURE] Detect and restore soft-deleted database-stored forms in the deleted-forms list
- [FEATURE] Make the "Columns" button in the results list work again, including proper modal close behaviour on save (broken since TYPO3 v14 dropped the old Bootstrap modal auto-init)
- [FEATURE] Add sortable column headers to the forms overview and per-form results list, and fix the "Form name" column header
- [FEATURE] Rework the results search form to use TYPO3's native search/sort handling, matching the Form Manager's own search field
- [FIX] Replace the TYPO3 v14-removed `SC_OPTIONS` form hooks with PSR-14 event listeners
- [FIX] Adapt to TYPO3 v14 breaking changes in `EXT:form` (`FormPersistenceManagerInterface`, `FormManagerController`, form session HMAC algorithm, and more)
- [FIX] Correct the backend module's parent module (`web` → `content`) so it appears in the module menu again
- [FIX] Remove the duplicate "Reload" button now added automatically by TYPO3 v14's doc header
- [FIX] Replace a removed Extbase magic `findBy()` repository call
- [FIX] Correct `deleteAllFormResultAction()`'s query and add missing `persistAll()` calls so backend deletions/restores are actually saved
- [FIX] Show the correct storage location ("Database") instead of the raw persistence identifier
- [FIX] Fix the PDF and CSV result downloads producing unopenable/misnamed files in some browsers
- [FIX] Remove a table header duplicating the page headline on the per-form results page
- [FIX] Fix the `form_to_database:deleteFormResults` command silently disappearing from the CLI and Scheduler module — TYPO3 v14's `#[AsCommand]` attribute-based auto-registration conflicted with the redundant manual `console.command` tag in `Services.yaml`, corrupting the command into a self-referential alias
- [TASK] Add a dedicated backend module icon
- [TASK] Bump `saschaegerer/phpstan-typo3` to `^3.0` for TYPO3 v14 compatibility
- [TASK] Add a ddev-based local environment with demo fixtures for manual QA

# 5.3.1

- [FIX] Include BaseSetup yaml in the frontend (#2)

# 5.3.0

- [FEATURE] Add delete all button (#17)
- [FIX] Prevent users without web-mounts seeing all forms (#23)

# 5.2.1

- [FIX] Add missing vendor names

# 5.2.0

- [!!!][FEATURE] - Rename composer package & namespace to Liquid Light,
  your code will need updating if you have extended the code (#10)
	- Replace `Lavitto` with `LiquidLight`
	- Replace `lavitto` with `liquidlight`

# 5.1.0

- [TASK] Raise minimum TYPO3 version requirement to 13.4.20
- [TASK] Add event FormResultSingleResultActionEvent
- [BUGFIX] check $variant['finishers'] before array_column(), refs (#134)
- [BUGFIX] Make functions in FormResultsController.php compatible with TYPO3 13.4.20
- [FIX] Remove duplicate array definition

# 5.0.2

- [SECURITY] Ensure array values are escaped

# 5.0.1

- [FIX] Import YAML by default (!87)

# 5.0.0

- [FEATURE] Add support for TYPO3 13 - TypoScript now included in SiteSets
- [FEATURE] Drop support for TYPO3 12
- [FEATURE] Add tests & linting

# 4.2.2

- [FIX] Use upload location when in container

# 4.2.1

- [FIX] Ignore user_upload folder if not used

# 4.2.0

- [FEATURE]  Remove modal from record view
- [FEATURE] Add PDF rendering view
- [FEATURE] Reorder repeatable fields in form results and number lines
- [TASK] output decoded html char on define extension option
- [BUGFIX] Initalize array $renderableFields for each $renderable in function hydrateRepeatableFields
- [BUGFIX] Ensure a uid & delimiter is defined
- [BUGFIX] CSV export - set order from original formDefinition instead of formState
- [BUGFIX] Form not visible in Backend Module when Finisher is set as variant
- [BUGFIX] Allow access to Form Results module from all workspaces
- [BUGFIX] Apply exclusion filter separately to child fields of parent container

# 4.1.0

- [TASK] Allow FormResult model to be extended
- [BUGFIX] Fixes access management for backend module

# 4.0.0

- [BREAKING] Drop TYPO3 11.5 support
- [TASK] Set PHP to 8.1 as a minimum
- [TASK] Refactoring of code for TYPO3 v12

# 3.0.1

- [BUGFIX] Make task schedulable

# 3.0.0

- [BREAKING] Drop TYPO3 9.5 support
- [BREAKING] Drop TYPO3 10.4 support
- [TASK] Refactoring of code
- [BUGFIX] Deleted fields are not shown in result (#89)
- [BUGFIX] Unique fields handling does not work (#88)
- [BUGFIX] Undefined array key list view (#87)
- [BUGFIX] Error when Editing Attributes (#67)
- [FEATURE] Allow dynamic child fields for an form element (#82)

# 2.2.1

- [BUG] Use Extconf API to retrieve config (#93)

# 2.2.0

⚠️ This release fixes a regression to re-enable the correct TYPO3 support. More details in [!37](https://gitlab.com/lavitto/typo3-form-to-database/-/merge_requests/37)

- [BREAKING] Drop TYPO3 11.5 support
- [BREAKING] Drop TYPO3 8.7 support
- [TASK] Set PHP to 7.4 as a minimum
- [TASK] Save repeatable fields to database (#59)
- [TASK] Improved marking when new entries (!36)
- [TASK] Set CSV to be comma separated by default (#83)
- [TASK] Incorporated the fix from Timo: !46
- [TASK] Moved listView states from fieldState to backenduser UC.
- [TASK] Made it possible to see which fields are deleted in the show view and the column selector.
- [TASK] Rename methods and variables to be more self explaining.
- [BUGFIX] added quotation marks around identifier numberOfResults because PostgreSQL changes unquoted identifiers to lowercase
- [BUGFIX] Fix undefined index (!30)
- [BUGFIX] Fix undefined array key issues with php 8
- [BUGFIX] Exception in Result List on multi-page form
- [BUGFIX] Nested elements should work. Fixed nested fields always marked deleted.
