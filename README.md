# LSX Currencies

The LSX Currencies extension adds currency selection functionality to sites, allowing users to view your products in whatever currencies you choose to sell in.

## Changelog

### 1.0.2
* Fix - Fixed all prefixes replaces (to_ > lsx_to_, TO_ > LSX_TO_)

### 1.0.1 - 08/12/16
* Fix - Reduced the access to server (check API key status) using transients
* Fix - Made the API URLs dev/live dynamic using a prefix "dev-" in the API KEY

### 1.0.0 - 30/11/16
* First Version

## Setup

### 1: Install NPM
https://nodejs.org/en/

### 2: Install Gulp
`npm install`

This will run the package.json file and download the list of modules to a "node_modules" folder in the plugin.

### 3: Gulp Commands
`gulp watch`
`gulp compile-sass`
`gulp compile-js`
`gulp wordpress-lang`