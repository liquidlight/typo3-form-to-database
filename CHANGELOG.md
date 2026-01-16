# 3.2.4

- [FIX] Compatibility with EXT:repeatable_form_elements (#73)

# 3.2.3

- [FIX] Initalize array `$renderableFields` for each `$renderable` in function hydrateRepeatableFields

# 3.2.2

- [SECURITY] Ensure array values are escaped

# 3.2.1

- [FIX] Use upload location when in container

# 3.2.0

- [FEATURE] Use properties from renderables

# 3.1.0

- [FEATURE] Remove single response modal and convert to a view
- [FEATURE] Allow single responses to be downloaded as a PDF

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
