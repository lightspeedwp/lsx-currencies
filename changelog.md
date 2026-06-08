# Change log

## [2.0.0] - 2026-06-08

### Added
- **Currency Switcher block** (`lsx-currencies/currency-switcher`) — a proper Gutenberg block replacing the old menu-injection approach. Supports appearance settings (flags, flag position, symbol position, horizontal/vertical layout, collapsed mode) via the block inspector. Server-side rendered via `render.php`; no PHP shortcode required.
- **Tour Operator admin integration** — plugin settings are now available as a "Currencies" submenu page under the Tour Operator menu (falls back to Settings when Tour Operator is not active).
- `class-block.php` — handles block registration (`register_block_type()`) and passes exchange-rate params to the frontend view script.

### Changed
- **Settings storage** migrated from the old UIX/Customizer multi-key approach to a single flat `lsx_currencies_settings` WordPress option.
- Frontend currency switching rewritten in **vanilla JavaScript** (no jQuery, no jquery-cookie). Uses native cookie helpers and the money.js + accounting.js libraries for price conversion.
- All admin field outputs and form saves now use proper nonces, `sanitize_key()`, `sanitize_text_field()`, `esc_attr()`, and `esc_html()` throughout.
- `class-woocommerce.php` — data-price attribute injection now uses `esc_attr()` to prevent XSS.
- `class-frontend.php` — data-price allowlist in `wp_kses_allowed_html` is now built dynamically from the enabled currencies list.
- `lsx-currencies.php` — version bumped to 2.0.0, sets `Requires at least: 7.0` and `Requires PHP: 8.0`.
- Build tooling replaced: Gulp removed, `@wordpress/scripts` (webpack) introduced for block compilation.

### Removed
- Old menu-injection currency switcher (`wp_nav_menu_items` filter) — replaced by the block.
- `[lsx_currency_value]` shortcode — replaced by the block.
- UIX framework admin pages and Customizer settings.
- `classes/deprecated/class-lsx-currencies.php` — legacy backwards-compatibility class.
- `includes/settings/` template partials — replaced by the new admin class.
- `assets/js/src/lsx-currencies.js` and `lsx-currencies-admin.js` — replaced by `src/blocks/currency-switcher/view.js`.

### Security
- All user inputs sanitized and nonce-verified throughout the admin.
- API URL constructed with `esc_url_raw()` before remote requests.
- Block render output escaped with `esc_attr()` / `esc_html()` at every interpolation point.
- Cookie read (`lsx_currencies_choice`) sanitized with `sanitize_key()`.
- WordPress 7.0 compatibility verified.

## [[1.2.7]](https://github.com/lightspeeddevelopment/lsx-currencies/releases/tag/1.2.7) - 2023-08-09

### Security
-   General testing to ensure compatibility with latest WordPress version (6.3).

## [[1.2.6]](https://github.com/lightspeeddevelopment/lsx-currencies/releases/tag/1.2.6) - 2023-04-20

### Fixed 
- The default currency not respecting the decimal restrictions.

### Security
-   General testing to ensure compatibility with latest WordPress version (6.2).

## [[1.2.5]](https://github.com/lightspeeddevelopment/lsx-currencies/releases/tag/1.2.5) - 2022-12-23

### Fixed
-	Added in additional conditions to the `facetwp_index_row_data` function to stop exmpty values breaking the saving.
-	Fixing the type error while saving values for the `facetwp_index_row_data` function
-   Updating PHP 8.0 compatability issues.

### Security
-   Removing Deprecated PHP 7.4 functions
-   General testing to ensure compatibility with latest WordPress version (6.1.1).

## [[1.2.4]](https://github.com/lightspeeddevelopment/lsx-currencies/releases/tag/1.2.4) - 2021-10-20

### Fixed
-	Changed the money_format to number_format, for PHP Deprecated functions.

### Security
-   General testing to ensure compatibility with latest WordPress version (5.8).

## [[1.2.3]](https://github.com/lightspeeddevelopment/lsx-currencies/releases/tag/1.2.3) - 2021-02-04

### Fixed
-	The pricing with setting a non base currency on LSX TO post types.
-   Removed the "float" case on the money format, which cause base currency values to convert to 0.

### Deprecated

-   Removed the deprecated tabs on the theme options.

### Security

-   Updating dependencies to prevent vulnerabilities.
-   General testing to ensure compatibility with latest WordPress version (5.5).
-   General testing to ensure compatibility with latest LSX Theme version (2.9).

## [[1.2.2]](https://github.com/lightspeeddevelopment/lsx-currencies/releases/tag/1.2.2) - 2020-03-30

### Added

-   Added in a function to get a converted value for the FacetWP Price slider when indexing and using multi currency.

### Fixed

-   Fixed PHP error `money_format() expects parameter 2 to be float, string given`.

### Security

-   Updating dependencies to prevent vulnerabilities.
-   General testing to ensure compatibility with latest WordPress version (5.4).
-   General testing to ensure compatibility with latest LSX Theme version (2.7).

## [[1.2.1]](https://github.com/lightspeeddevelopment/lsx-currencies/releases/tag/1.2.1) - 2019-11-13

### Added

-   Adding the .gitattributes file to remove unnecessary files from the WordPress version.
-   Removing the lsx-currencies admin js.

### Fixed

-   Making sure the money format stays constant.
-   Fixing the reloading of prices on facet selection.

## [[1.2.0]](https://github.com/lightspeeddevelopment/lsx-currencies/releases/tag/1.2.0) - 2019-09-04

### Added

-   Code Standards updates.
-   Added in a fallback API for exchange rates.
-   Namespacing and instances for all classes.
-   Added in an option to convert all multiple currencies to your base currency on the frontend.
-   Added in a shortcode - [lsx_currency_value value="500"].
-   Added in the option to strip the decimal places from the values.
-   Allowing the base currency to be overwitten by the WooCommerce currency if the plugin is active.

## [[1.1.2]]()

### Added

-   Added in WooCommerce integration.
-   Adding `noopener noreferrer` tags to target="\_blank" links.
-   Adding the travis and codesniffer ruleset.
-   Adding a readme.txt and updating the readme.md.

## [[1.1.1]]()

### Added

-   Added compatibility with LSX Videos.
-   Added compatibility with LSX Search.

### Fixed

-   Move UIX settings (visual tab) to WP Customizer.

## [[1.1.0]](https://github.com/lightspeeddevelopment/lsx-currencies/releases/tag/1.2.0) - 2017-10-10

### Added

-   Added compatibility with LSX 2.0.
-   New project structure.
-   UIX copied from TO 1.1 + Fixed issue with sub tabs click (settings).

### Fixed

-   Fixing the settings integration with the Tour Operator Plugin.
-   Fixed scripts/styles loading order.
-   Fixed small issues.

## [[1.0.4]]()

### Added

-   Added in a transient call to cache the Rates from the exchange API.

### Fixed

-   Restricted the API calls to the frontend only reducing the number of queries.

## [[1.0.3]]()

### Fixed

-   Adjusted the plugin settings link inside the LSX API Class.

## [[1.0.2]]()

### Fixed

-   Fixed all prefixes replaces (to* > lsx_to*, TO* > LSX_TO*).

## [[1.0.1]]()

### Fixed

-   Reduced the access to server (check API key status) using transients.
-   Made the API URLs dev/live dynamic using a prefix "dev-" in the API KEY.

## [[1.0.0]]()

### Added

-First Version.
