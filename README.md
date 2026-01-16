# TYPO3 Extension `Form to Database`

[![Latest Stable Version](https://img.shields.io/packagist/v/liquidlight/typo3-form-to-database?style=for-the-badge)](https://packagist.org/packages/liquidlight/typo3-form-to-database)
[![TYPO3](https://img.shields.io/badge/TYPO3-form_to_database-%23f49700?style=for-the-badge)](https://extensions.typo3.org/extension/form_to_database/)
[![License](https://img.shields.io/packagist/l/liquidlight/typo3-form-to-database?style=for-the-badge)](https://packagist.org/packages/liquidlight/typo3-form-to-database)

> This extension adds an additional finisher to the TYPO3 Form (tx_form) to save the results into the database

- **GitHub Repository**: [github.com/liquidlight/typo3-form-to-database](https://github.com/liquidlight/typo3-form-to-database)
- **TYPO3 Extension Repository**: [extensions.typo3.org/extension/form_to_database](https://extensions.typo3.org/extension/form_to_database)
- **Found an issue?**: [github.com/liquidlight/typo3-form-to-database/issues](https://github.com/liquidlight/typo3-form-to-database/issues)

```code
composer req liquidlight/typo3-form-to-database
```

## Note on version

Version 2.* (from 2.2.0) will support TYPO3 V9 and V10 **only**. Version 11+ support can be found on 3.*.

Any bug fixes to 2.* need to be carried out on the `version/2.x` branch


## 1. Introduction

### Features

- No configuration needed
- No database-changes per form required
- Shows all results per form in a separate backend module
- Provides a CSV-download of all results
- Automatic deletion of results after a specified number of days (GDPR)

### Screenshots

#### Backend Overview

![Backend Overview](Documentation/Images/typo3-form-to-database-backend-overview_tmb.png)
- [Full Size Screenshot](Documentation/Images/typo3-form-to-database-backend-overview.png)

#### Backend Results

![Backend Results](Documentation/Images/typo3-form-to-database-backend-results_tmb.png)
- [Full Size Screenshot](Documentation/Images/typo3-form-to-database-backend-results.png)

## 2. Installation

### Installation using Composer

The recommended way to install the extension is by using [Composer](https://getcomposer.org/). In your Composer based
TYPO3 project root run `composer req liquidlight/typo3-form-to-database`.

### Installation from TYPO3 Extension Repository (TER)

Download and install the extension `form_to_database` with the extension manager module.

## 3. Minimal setup

Add the finisher ("Save the mail to the Database") to your forms to start storing data.

## 4. Command / Scheduler

It's possible to delete the form results by the command line or scheduler (Execute console commands).

```shell script
Usage:
  form_to_database:deleteFormResults [<maxAge>]

Arguments:
  maxAge                Maximum age of form results in days [default: 90]
```

## 5. Contribute

Please create an issue at https://github.com/liquidlight/typo3-form-to-database/issues.

**Please use GitLab only for bug-reporting or feature-requests. For support use the TYPO3 community channels or contact us by email.**

## 6. Support

If you need private or personal support, try the TYPO3 Slack channel - [#ext-form-to-database](https://app.slack.com/client/T024TUMLZ/C02HWBCUF0F) or contact us by email on [info@liquidlight.co.uk](mailto:info@liquidlight.co.uk).

**Be aware that this support might not be free!**

## Contributors

Big thanks to the following people

- ⭐️ [Philipp Mueller](https://www.lavitto.ch) - For providing the original code & extension
- Markus Hofmann
- Stig Nørgaard Færch
