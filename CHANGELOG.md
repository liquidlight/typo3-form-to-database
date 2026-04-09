# 4.4.2

- [FIX] Resolve incorrect 4.4.1 tag

# 4.4.1

- [FIX] Prevent users without web-mounts seeing all forms (#23)

# 4.4.0

- [!!!][FEATURE] - Rename composer package & namespace to Liquid Light,
  your code will need updating if you have extended the code
	- Replace `Lavitto` with `LiquidLight`
	- Replace `lavitto` with `liquidlight`

# 4.3.1

- [FIX] Compatibility with EXT:repeatable_form_elements (#73)

# 4.3.0

- [TASK] Add event FormResultSingleResultActionEvent
- [FIX] Filtered CSV download only visible fields not working
- [FIX] check `$variant['finishers']` before `array_column()` (#134)

# 4.2.3

- [SECURITY] Ensure array values are escaped

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
