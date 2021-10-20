# Change log

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
