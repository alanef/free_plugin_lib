# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

# [1.2.2] - 2025-12-21
### Changed
- Replaced default endpoint for webooks


## [1.2.1] - 2025-12-15
### Fixed
- PHPCS warnings for input validation and sanitization
- Added wp_unslash() and sanitization for all superglobal access
- Fixed admin notice page detection to use get_current_screen()->base

## [1.2.0] - 2025-12-14
### Changed
- Replaced activation hook approach with first-run detection pattern
- Opt-in prompt now works regardless of when consuming plugin instantiates Main class

### Added
- Dismissible admin notice on dashboard, plugins page, and settings page prompting users to check settings
- AJAX handler for notice dismissal with user meta storage
- Nonce verification for skip action
- Filters `ffpl_plugin_map` and `ffpl_verify_url` for testing/customization

### Fixed
- Activation hook not firing when Main instantiated on `plugins_loaded` or later
- Notice dismissal no longer incorrectly sets opt-out status
- Opt-out only set when user explicitly clicks "Skip"

## [1.1.0] - 2025-12-08
### Changed
- Updated email endpoint to new verify.workflow.fw9.uk service
- Added plugin ID mapping for opt-in submissions

## [1.0.1] - 2025-01-27
### Fixed
- Fixed issue when two plugins use the same lib the optin page is confused

## [1.0.0] - 2025-01-26

### Added
- First release