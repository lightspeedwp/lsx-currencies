/**
 * Currency Switcher Block — Frontend View Script
 *
 * Handles currency switching on the frontend using vanilla JS.
 * Reads/writes the selected currency to a cookie and converts all
 * price spans (.amount.lsx-currencies) on the page using money.js
 * and accounting.js (enqueued via class-block.php).
 *
 * @package LSX Currencies
 */

/* global lsx_currencies_params, fx, accounting */

( function () {
	'use strict';

	// -------------------------------------------------------------------------
	// Cookie helpers (no jQuery dependency)
	// -------------------------------------------------------------------------

	/**
	 * Read a cookie value by name.
	 *
	 * @param {string} name Cookie name.
	 * @return {string|null}
	 */
	function getCookie( name ) {
		const value = '; ' + document.cookie;
		const parts = value.split( '; ' + name + '=' );
		if ( parts.length === 2 ) {
			return decodeURIComponent( parts.pop().split( ';' ).shift() );
		}
		return null;
	}

	/**
	 * Write a cookie.
	 *
	 * @param {string} name  Cookie name.
	 * @param {string} value Cookie value.
	 * @param {number} days  Expiry in days (default 30).
	 */
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

	/**
	 * Sanitise a currency code to uppercase letters only (max 3 chars).
	 *
	 * @param {string} raw Raw input.
	 * @return {string}
	 */
	function sanitiseCurrencyCode( raw ) {
		return String( raw ).replace( /[^A-Za-z]/g, '' ).toUpperCase().slice( 0, 3 );
	}

	// -------------------------------------------------------------------------
	// Core logic
	// -------------------------------------------------------------------------

	const params = ( typeof lsx_currencies_params !== 'undefined' ) ? lsx_currencies_params : null;

	if ( ! params ) {
		return;
	}

	const decimalPlaces = params.removeDecimals ? 0 : 2;

	/**
	 * Re-render all price spans for the given currency.
	 *
	 * @param {string} targetCurrency ISO 4217 code.
	 */
	function updatePrices( targetCurrency ) {
		if ( typeof fx === 'undefined' || typeof accounting === 'undefined' ) {
			return;
		}

		const amounts = document.querySelectorAll( '.amount.lsx-currencies' );

		amounts.forEach( function ( wrapper ) {
			const valueEl = wrapper.querySelector( '.value' );
			if ( ! valueEl ) {
				return;
			}

			// Try to read a pre-stored data-price-{CURRENCY} attribute first.
			const dataKey = 'data-price-' + targetCurrency;
			const rawAttr = wrapper.getAttribute( dataKey ) || valueEl.getAttribute( dataKey );

			let convertedAmount;

			if ( rawAttr !== null && rawAttr !== '' ) {
				convertedAmount = parseFloat( rawAttr );
			} else {
				// Fall back to live conversion via money.js.
				const basePriceAttr = 'data-price-' + params.base;
				const basePrice = parseFloat(
					wrapper.getAttribute( basePriceAttr ) ||
					valueEl.getAttribute( basePriceAttr ) ||
					0
				);

				try {
					convertedAmount = fx( basePrice )
						.from( params.base )
						.to( targetCurrency );
				} catch ( e ) {
					convertedAmount = basePrice;
				}
			}

			// Format using accounting.js.
			const symbol = ( params.symbols && params.symbols[ targetCurrency ] ) ? params.symbols[ targetCurrency ] : targetCurrency;
			valueEl.textContent = accounting.formatNumber( convertedAmount, decimalPlaces );

			// Update the currency icon if present.
			const iconEl = wrapper.querySelector( '.currency-icon' );
			if ( iconEl ) {
				iconEl.className = 'currency-icon ' + targetCurrency.toLowerCase();
				iconEl.textContent = targetCurrency;
			}
		} );
	}

	/**
	 * Set the active visual state on switcher links.
	 *
	 * @param {string} currency ISO 4217 code.
	 */
	function setActiveSwitcher( currency ) {
		const items = document.querySelectorAll( '.lsx-currency-item' );
		items.forEach( function ( item ) {
			const link = item.querySelector( 'a' );
			if ( ! link ) {
				return;
			}
			const href = link.getAttribute( 'href' ) || '';
			const code = sanitiseCurrencyCode( href.replace( '#', '' ) );
			if ( code === currency ) {
				item.classList.add( 'lsx-currency-current' );
				link.setAttribute( 'aria-current', 'true' );
			} else {
				item.classList.remove( 'lsx-currency-current' );
				link.removeAttribute( 'aria-current' );
			}
		} );
	}

	/**
	 * Switch to the given currency: update cookie, prices, and UI.
	 *
	 * @param {string} currency ISO 4217 code.
	 */
	function switchCurrency( currency ) {
		const code = sanitiseCurrencyCode( currency );
		if ( ! code ) {
			return;
		}
		setCookie( 'lsx_currencies_choice', code );
		updatePrices( code );
		setActiveSwitcher( code );
	}

	// -------------------------------------------------------------------------
	// Initialise money.js once DOM is ready
	// -------------------------------------------------------------------------

	function init() {
		if ( typeof fx === 'undefined' ) {
			return;
		}

		// Configure money.js.
		fx.base  = 'USD'; // Exchange rates API always returns USD as base.
		fx.rates = params.rates || {};

		// Determine current currency.
		const stored   = getCookie( 'lsx_currencies_choice' );
		const current  = sanitiseCurrencyCode( stored || params.current || params.base );

		// Initial render.
		updatePrices( current );
		setActiveSwitcher( current );

		// -----------------------------------------------------------------------
		// Attach click handlers to all currency switcher links.
		// -----------------------------------------------------------------------
		document.querySelectorAll( '.lsx-currency-switcher' ).forEach( function ( switcher ) {
			const showCurrentOnly = switcher.classList.contains( 'lsx-show-current-only' );

			// Toggle open/close for collapsed mode.
			if ( showCurrentOnly ) {
				const toggle = switcher.querySelector( '.lsx-currency-toggle' );
				if ( toggle ) {
					toggle.addEventListener( 'click', function ( e ) {
						e.preventDefault();
						switcher.classList.toggle( 'lsx-switcher-open' );
					} );
				}

				// Close on outside click.
				document.addEventListener( 'click', function ( e ) {
					if ( ! switcher.contains( e.target ) ) {
						switcher.classList.remove( 'lsx-switcher-open' );
					}
				} );
			}

			// Currency link clicks.
			switcher.querySelectorAll( '.lsx-currency-item a' ).forEach( function ( link ) {
				link.addEventListener( 'click', function ( e ) {
					e.preventDefault();
					const href = link.getAttribute( 'href' ) || '';
					const code = sanitiseCurrencyCode( href.replace( '#', '' ) );
					if ( code ) {
						switchCurrency( code );
						if ( showCurrentOnly ) {
							switcher.classList.remove( 'lsx-switcher-open' );
						}
					}
				} );
			} );
		} );
	}

	// Run after DOM + enqueued scripts (money.js / accounting.js) are ready.
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
