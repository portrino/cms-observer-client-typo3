# Contributing

Thank you for considering contributing to this project! Every contribution is welcome and helps improve the quality of 
the project. To ensure a smooth process and maintain high code quality, please follow the steps below.

## Requirements

Uses [DDEV]([DDEV](https://ddev.readthedocs.io/en/stable/)) with one TYPO3 instance per supported version (`.Build/12`,
`.Build/13`, `.Build/14`):

## Preparation

```bash
# Clone repository
git clone https://github.com/portrino/cms-observer-client-typo3.git
cd cms-observer-client-typo3

# Start the project with DDEV
ddev start

# Install dependencies
ddev composer install
```

## Run composer scripts

```bash
# Run all cgl + static code analyzers
ddev composer ci:static

# Specific CGL/ static code analyzers
ddev composer ci:fractor
ddev composer ci:json:lint
ddev composer ci:php:cs-fixer
ddev composer ci:php:lint
ddev composer ci:php:rector
ddev composer ci:php:stan
ddev composer ci:ts:lint
ddev composer ci:yaml:lint

# Fix all CGL/ SCA issues
ddev composer fix

# Fix specific CGL/ SCA issues
ddev composer fix:fractor
ddev composer fix:php:cs
ddev composer fix:php:rector
```

## Run tests

```bash
# Run all tests
ddev composer test

# Run unit tests (with code coverage)
ddev composer test:unit
ddev composer test:unit:coverage
```

## TYPO3 Setup

For testing the extension, you need to set up the TYPO3 instances.

```bash
# Install all TYPO3 versions, which are supported by the extension
ddev install all

# Or install specific TYPO3 versions
ddev install 12
ddev install 13
ddev install 14

# Open the overview page
ddev launch

# Run TYPO3 specific commands
ddev all typo3 database:updateschema
ddev 12 typo3 cache:flush
ddev 13 composer install

# Use dry-run to verify client payload
ddev 14 typo3 cms-observer:push --dry-run
```

## Submit a pull request

After completing your work, **open a pull request** and provide a description of your changes. Ideally, your PR should reference an issue that explains the problem you are addressing.

All mentioned code quality tools will run automatically on every pull request. For more details, see the relevant [workflows][1].

[1]: .github/workflows
