# Free Plugin Library for WordPress

This library is designed to be integrated into free WordPress plugins, providing essential features such as opt-in forms, settings page management, and promotional content for premium plugins. It is distributed via **Packagist** and complies with WordPress.org guidelines.

---

## Features

- **Opt-in Form**: Allows users to opt-in to email lists for updates, tips, and exclusive offers.
- **Settings Page Management**: Adds a settings link to the plugin action links and redirects users to the settings page upon activation.
- **Promotional Content**: Displays ads for related premium plugins on the settings page, encouraging users to upgrade.
- **Compliance**: Ensures compliance with WordPress.org guidelines, including explicit consent for data collection and clear privacy policies.

---

## Installation

This library is available via **Packagist**. To install it in your WordPress plugin, use Composer:

```bash
composer require alanef/free_plugin_lib
```

When you build for plugin release use `composer update --no-dev` flag ensures that only production-ready code is installed, excluding development assets like tests and CI configurations.

---

## Usage

### Initialization
To use the library, initialize it in your plugin's main file. You can instantiate it on any hook (e.g., `plugins_loaded`):

```php
use Fullworks_Free_Plugin_Lib\Main;

add_action('plugins_loaded', function() {
    new Main(
        __FILE__,                                      // plugin_file
        'options-general.php?page=your-plugin-settings', // settings_page URL
        'your_plugin_shortname',                       // plugin_shortname (unique identifier)
        'your-plugin-page',                            // page slug
        'Your Plugin Name'                             // display name
    );
});
```

### Opt-in Form
The library automatically handles the opt-in form display using a non-intrusive approach:

1. **Admin Notice**: A dismissible notice appears on the dashboard, plugins page, and your plugin's settings page prompting users to complete setup
2. **First Settings Visit**: When users first visit your plugin's settings page, they are redirected to the opt-in form
3. **User Choice**: Users can opt-in, skip, or dismiss the notice - the plugin works regardless of their choice

### Settings Page
The library adds a settings link to the plugin action links. The opt-in prompt appears on first use, not on activation, ensuring it works with all activation methods (UI, WP-CLI, bulk activation, etc.).

### Promotional Content
The library displays ads for premium plugins on the settings page. Ensure that the `Advert` class is correctly initialized and that the premium plugin check logic is in place.

---

## Hooks and Filters

### Actions
- `admin_menu`: Adds the settings page to the WordPress admin menu.
- `admin_notices`: Displays setup prompt notice on dashboard, plugins page, and settings page.
- `init`: Loads the text domain for localization.
- `admin_enqueue_scripts`: Enqueues necessary scripts and styles for the settings page.
- `ffpl_ad_display`: Displays promotional content for premium plugins.

### Filters
- `plugin_action_links`: Adds a settings link to the plugin action links.
- `ffpl_plugin_map`: Allows adding custom plugin shortnames to the verification endpoint mapping.
- `ffpl_verify_url`: Allows overriding the verification endpoint URL (useful for testing).

---

## Localization
The library supports localization. Translation files should be placed in the `languages` directory and named according to the text domain (`free-plugin-lib`).

Example:
```
languages/free-plugin-lib-en_US.mo
languages/free-plugin-lib-en_US.po
```

---

## Compliance
The library is designed to comply with WordPress.org guidelines, including:
- **Explicit Consent**: No data is sent to external servers without explicit user consent.
- **No Automatic Downloads**: No software is downloaded or installed without user consent.
- **Clear Privacy Policy**: The opt-in form includes a link to the privacy policy.

---

## Contributing
Contributions are welcome! If you'd like to contribute to the development of this library, follow these steps:

1. Fork the repository.
2. Clone your fork locally:
   ```bash
   git clone https://github.com/your-username/free_plugin_lib.git
   ```
3. Install development dependencies:
   ```bash
   composer install
   ```
4. Make your changes and run tests:
   ```bash
   vendor/bin/phpunit
   ```
5. Submit a pull request with your changes.

---

## License
This library is licensed under the **GPL-2.0-or-later** license. See the [LICENSE](LICENSE) file for more details.

---

## Support
For support, please open an issue on the [GitHub repository](https://github.com/alanef/free_plugin_lib) or contact the maintainer directly.

---

## Changelog
For a detailed list of changes, see the [CHANGELOG](CHANGELOG.md).

---

