# TYPO3 Extension `Form to Database`

[![Donate](https://img.shields.io/badge/Donate-PayPal-green.svg?style=for-the-badge)](https://paypal.me/pmlavitto)
[![Latest Stable Version](https://img.shields.io/packagist/v/lavitto/typo3-form-to-database?style=for-the-badge)](https://packagist.org/packages/lavitto/typo3-form-to-database)
[![TYPO3](https://img.shields.io/badge/TYPO3-form_to_database-%23f49700?style=for-the-badge)](https://extensions.typo3.org/extension/form_to_database/)
[![License](https://img.shields.io/packagist/l/lavitto/typo3-form-to-database?style=for-the-badge)](https://packagist.org/packages/lavitto/typo3-form-to-database)

> This extension adds an additional finisher to the TYPO3 Form (tx_form) to save the results into the database

- **Gitlab Repository**: [gitlab.com/lavitto/typo3-form-to-database](https://gitlab.com/lavitto/typo3-form-to-database)
- **TYPO3 Extension Repository**: [extensions.typo3.org/extension/form_to_database](https://extensions.typo3.org/extension/form_to_database)
- **Found an issue?**: [gitlab.com/lavitto/typo3-form-to-database/issues](https://gitlab.com/lavitto/typo3-form-to-database/issues)

## Compatibility

| Form to Database | TYPO3 Version |
| ---------------- | ------------- |
| 4.x              | 12.4          |
| 3.x              | 11.5          |
| 2.x              | 9.5 - 10.4    |

## Introduction

### Features

- No configuration needed
- No database-changes per form required
- Shows all results per form in a separate backend module
- Provides a CSV-download of all results
- Automatic deletion of results after a specified number of days (GDPR)
- Preview & PDF download of individual form results

### Screenshots

#### Backend Overview

![Backend Overview](https://cdn.lavitto.ch/typo3/lavitto/typo3-form-to-database/typo3-form-to-database-backend-overview_tmb.png)
- [Full Size Screenshot](https://cdn.lavitto.ch/typo3/lavitto/typo3-form-to-database/typo3-form-to-database-backend-overview.png)

#### Backend Results

![Backend Results](https://cdn.lavitto.ch/typo3/lavitto/typo3-form-to-database/typo3-form-to-database-backend-results_tmb.png)
- [Full Size Screenshot](https://cdn.lavitto.ch/typo3/lavitto/typo3-form-to-database/typo3-form-to-database-backend-results.png)

## Installation

### Installation using Composer

The recommended way to install the extension is by using [Composer](https://getcomposer.org/). In your Composer based
TYPO3 project root run `composer req lavitto/typo3-form-to-database`.

### Installation from TYPO3 Extension Repository (TER)

Download and install the extension `form_to_database` with the extension manager module.

## Setup & Usage

### Finisher

To start storing form results:

1. Install the extension
2. Edit the form you wish to store results for
3. Add the finisher ("Save the mail to the Database") to your form - it is recommended you place this finisher first

### Command / Scheduler

It's possible to delete the form results by the command line or scheduler (Execute console commands).

```shell script
Usage:
  form_to_database:deleteFormResults [<maxAge>]

Arguments:
  maxAge                Maximum age of form results in days [default: 90]
```

### Customise

There are several options available for customisation. To change these, go to **Settings** -> **Configure Extensions** -> **form_to_database**

- **General**
  - `hideLocationInList` - Should the location of the form be hidden on the Form results overview module? (Default: No)
- **CSV Settings**
  - `csvDelimiter` - What character should separate fields in the CSV export (Default: `,`)
  - `csvOnlyFilenameOfUploadFields` - Should the CSV list the whole path or just the file name?

## Contribute

Please create an issue at https://gitlab.com/lavitto/typo3-form-to-database/issues.

**Please use GitLab only for bug-reporting or feature-requests. For support use the TYPO3 community channels or contact us by email.**

Commits should follow [TYPO3 Commit Guidelines](https://docs.typo3.org/m/typo3/guide-contributionworkflow/main/en-us/Appendix/CommitMessage.html#commitmessage).

## Support

If you need private or personal support, try the TYPO3 Slack channel - [#ext-form-to-database](https://app.slack.com/client/T024TUMLZ/C02HWBCUF0F) or contact us by email on [info@lavitto.ch](mailto:info@lavitto.ch).

**Be aware that this support might not be free!**
