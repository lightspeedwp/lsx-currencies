/**
 * Currency Switcher Block — Frontend View Script
 *
 * Handles currency switching on the frontend using vanilla JS.
 * Works with the navigation-submenu HTML structure output by render.php:
 *   - Top-level: <a> or <button> inside <li.wp-block-navigation-item.has-child>
 *   - Submenu items: <a data-lsx-currency="CODE"> inside
 *     <ul.wp-block-navigation__submenu-container>
 *
 * On currency selection:
 *   1. Write cookie.
 *   2. Convert all price spans (.amount.lsx-currencies) via money.js + accounting.js.
 *   3. Swap the top-level label and rebuild the submenu in-place (no page reload).
 *
 * @package LSX Currencies
 */

/* global lsx_currencies_params, fx, accounting */

( function () {
	'use strict';

	// -------------------------------------------------------------------------
	// Cookie helpers
	// -------------------------------------------------------------------------

	function getCookie( name ) {
		const value = '; ' + document.cookie;
		const parts = value.split( '; ' + name + '=' );
		if ( parts.length === 2 ) {
			return decodeURIComponent( parts.pop().split( ';' ).shift() );
		}
		return null;
	}

	function setCookie( name, value, days ) {
		const d = new Date();
		d.setTime( d.getTime() + ( ( days || 30 ) * 24 * 60 * 60 * 1000 ) );
		document.cookie =
			name + '=' + encodeURIComponent( value ) +
			';expires=' + d.toUTCString() +
			';path=/;SameSite=Lax';
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	function sanitiseCurrencyCode( raw ) {
		return String( raw ).replace( /[^A-Za-z]/g, '' ).toUpperCase().slice( 0, 3 );
	}

	// -------------------------------------------------------------------------
	// Price conversion
	// -------------------------------------------------------------------------

	const params = ( typeof lsx_currencies_params !== 'undefined' ) ? lsx_currencies_params : null;

	if ( ! params ) {
		return;
	}

	const decimalPlaces = params.removeDecimals ? 0 : 2;

	function updatePrices( targetCurrency ) {
		if ( typeof fx === 'undefined' || typeof accounting === 'undefined' ) {
			return;
		}

		document.querySelectorAll( '.amount.lsx-currencies' ).forEach( function ( wrapper ) {
			const valueEl = wrapper.querySelector( '.value' );
			if ( ! valueEl ) {
				return;
			}

			const dataKey   = 'data-price-' + targetCurrency;
			const rawAttr   = wrapper.getAttribute( dataKey ) || valueEl.getAttribute( dataKey );
			let converted;

			if ( rawAttr !== null && rawAttr !== '' ) {
				converted = parseFloat( rawAttr );
			} else {
				const basePriceAttr = 'data-price-' + params.base;
				const basePrice = parseFloat(
					wrapper.getAttribute( basePriceAttr ) ||
					valueEl.getAttribute( basePriceAttr ) ||
					0
				);
				try {
					converted = fx( basePrice ).from( params.base ).to( targetCurrency );
				} catch ( e ) {
					converted = basePrice;
				}
			}

			valueEl.textContent = accounting.formatNumber( converted, decimalPlaces );

			const iconEl = wrapper.querySelector( '.currency-icon' );
			if ( iconEl ) {
				iconEl.className = 'currency-icon ' + targetCurrency.toLowerCase();
				iconEl.textContent = targetCurrency;
			}
		} );
	}

	// -------------------------------------------------------------------------
	// Switcher DOM update
	//
	// The switcher is a server-rendered <li class="wp-block-navigation-item has-child">.
	// When the visitor picks a new currency we:
	//   a) update the top-level label text
	//   b) remove the newly selected <li> from the submenu
	//   c) insert a new <li> for the previously current currency into the submenu
	// -------------------------------------------------------------------------

	/**
	 * Build the label HTML for a currency code, optionally including the symbol.
	 *
	 * @param {string}  code       ISO 4217 code (uppercase).
	 * @param {boolean} showSymbol Whether to append the currency symbol.
	 * @return {string} HTML string.
	 */
	function buildLabelHTML( code, showSymbol ) {
		let html = '<span class="wp-block-navigation-item__label">' + code;
		if ( showSymbol && params.symbols && params.symbols[ code ] ) {
			html += ' <span class="lsx-currency-symbol" aria-hidden="true">' + params.symbols[ code ] + '</span>';
		}
		html += '</span>';
		return html;
	}

	/**
	 * Update a single currency switcher block.
	 *
	 * @param {Element} switcherLi  The <li class="wp-block-navigation-item has-child"> element.
	 * @param {string}  newCurrency Newly selected ISO 4217 code.
	 * @param {string}  oldCurrent  Previously active ISO 4217 code.
	 */
	function updateSwitcher( switcherLi, newCurrency, oldCurrent ) {
		const showSymbol = switcherLi.dataset.showSymbol === '1';

		// ── Update top-level label ────────────────────────────────────────────
		const topContent = switcherLi.querySelector( ':scope > .wp-block-navigation-item__content' );
		if ( topContent ) {
			topContent.innerHTML = buildLabelHTML( newCurrency, showSymbol );
			topContent.setAttribute( 'href', '#' + newCurrency.toLowerCase() );
		}

		// ── Rebuild submenu ───────────────────────────────────────────────────
		const submenu = switcherLi.querySelector( ':scope > .wp-block-navigation__submenu-container' );
		if ( ! submenu ) {
			return;
		}

		// Remove the newly selected currency from the submenu.
		const toRemove = submenu.querySelector( 'a[data-lsx-currency="' + newCurrency + '"]' );
		if ( toRemove ) {
			toRemove.closest( 'li' )?.remove();
		}

		// Add the previously active currency into the submenu if it is not already there.
		const alreadyPresent = submenu.querySelector( 'a[data-lsx-currency="' + oldCurrent + '"]' );
		if ( ! alreadyPresent ) {
			const li = document.createElement( 'li' );
			li.className = 'wp-block-navigation-item wp-block-navigation-link';
			const a = document.createElement( 'a' );
			a.className = 'wp-block-navigation-item__content';
			a.href = '#' + oldCurrent.toLowerCase();
			a.dataset.lsxCurrency = oldCurrent;
			a.innerHTML = buildLabelHTML( oldCurrent, showSymbol );
			li.appendChild( a );
			submenu.appendChild( li );

			// Attach click handler to the newly inserted item.
			attachItemHandler( switcherLi, a );
		}
	}

	// Track the live current currency across all switchers.
	let activeCurrency = sanitiseCurrencyCode(
		getCookie( 'lsx_currencies_choice' ) || params.current || params.base
	);

	function switchCurrency( newCode ) {
		const code = sanitiseCurrencyCode( newCode );
		if ( ! code || code === activeCurrency ) {
			return;
		}

		const previous = activeCurrency;
		activeCurrency = code;

		setCookie( 'lsx_currencies_choice', code );
		updatePrices( code );

		document.querySelectorAll( '.wp-block-lsx-currencies-currency-switcher' ).forEach( function ( switcher ) {
			updateSwitcher( switcher, code, previous );
		} );
	}

	// -------------------------------------------------------------------------
	// Click handler attachment
	// -------------------------------------------------------------------------

	function attachItemHandler( switcherLi, linkEl ) {
		linkEl.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			const code = sanitiseCurrencyCode( linkEl.dataset.lsxCurrency || '' );
			if ( code ) {
				switchCurrency( code );
				// Close the submenu (navigation block manages aria-expanded state via its own JS;
				// simulate a blur to allow it to close naturally).
				switcherLi.classList.remove( 'is-menu-open' );
				const toggle = switcherLi.querySelector( '.wp-block-navigation-submenu__toggle' );
				if ( toggle && toggle.getAttribute( 'aria-expanded' ) === 'true' ) {
					toggle.click();
				}
			}
		} );
	}

	function attachSwitcherHandlers( switcherLi ) {
		switcherLi.querySelectorAll( 'a[data-lsx-currency]' ).forEach( function ( link ) {
			attachItemHandler( switcherLi, link );
		} );
	}

	// -------------------------------------------------------------------------
	// Initialise
	// -------------------------------------------------------------------------

	function init() {
		if ( typeof fx !== 'undefined' ) {
			fx.base  = 'USD';
			fx.rates = params.rates || {};
		}

		const stored = getCookie( 'lsx_currencies_choice' );
		if ( stored ) {
			activeCurrency = sanitiseCurrencyCode( stored );
		}

		if ( typeof fx !== 'undefined' && typeof accounting !== 'undefined' ) {
			updatePrices( activeCurrency );
		}

		document.querySelectorAll( '.wp-block-lsx-currencies-currency-switcher' ).forEach( function ( switcher ) {
			attachSwitcherHandlers( switcher );
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
