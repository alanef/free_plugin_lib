# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.3.0] - 2026-10-02
### Fixed
- A host plugin's own uninstall routine never ran when the plugin was deleted, so its settings were left behind. The library's constructor registered its own uninstall hook for the same plugin file. WordPress keeps only one uninstall callback per plugin, so the two registrations replaced each other, and the library's always won. This also caused two database writes to the `uninstall_plugins` option on every request. The library no longer registers an uninstall hook.

### Changed
- **Action needed in host plugins:** call `\Fullworks_Free_Plugin_Lib\Main::plugin_uninstall( 'your_shortname' )` from your own uninstall handler (or `uninstall.php`) to remove the library's `{shortname}_form_rendered` option. `plugin_uninstall()` now accepts the shortname as an optional argument, because `Main` may not have been constructed when the uninstall callback runs.

### Removed
- The premium anti-spam advert is no longer shown on settings pages. The library no longer attaches anything to the `ffpl_ad_display` action, so plugins that still call `do_action( 'ffpl_ad_display' )` keep working and simply show nothing. The `Advert` class and its image have been deleted.

# [1.2.4] - 2026-02-05
### Fixed
- Fix ad displaying multiple times when multiple plugins use the library

# [1.2.3] - 2025-12-21
### Changed
- Transpose CSCF  to cfcs

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