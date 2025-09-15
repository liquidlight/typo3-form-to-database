# 2.2.4

- [BUGFIX] Issues when creating new fields (!53)
- [BUGFIX] Errors in results list in backend module when SummaryPage used
- [FIX] Initalize array $renderableFields for each $renderable in function hydrateRepeatableFields (#126)
- [FIX] Use upload location when in container (#73)
- [FIX]: Ignore user_upload folder if not used (#73)

# 2.2.3

- [BUGFIX] Backport bugfixes from main to v2 (#130)

# 2.2.2

- [BUG] Reinstate commands file to register commands

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
