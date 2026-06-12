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

	const LOG = '[lsx-currencies]';

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
		console.warn( LOG, 'lsx_currencies_params not found — script not localised.' );
		return;
	}

	console.log( LOG, 'params loaded', {
		base: params.base,
		current: params.current,
		removeDecimals: params.removeDecimals,
		convertToSingle: params.convertToSingle,
		ratesMessage: params.ratesMessage,
		rateCount: params.rates ? Object.keys( params.rates ).length : 0,
	} );

	const decimalPlaces = params.removeDecimals ? 0 : 2;

	function updatePrices( targetCurrency ) {
		console.log( LOG, 'updatePrices()', targetCurrency );

		if ( typeof fx === 'undefined' ) {
			console.warn( LOG, 'money.js (fx) not loaded' );
			return;
		}
		if ( typeof accounting === 'undefined' ) {
			console.warn( LOG, 'accounting.js not loaded' );
			return;
		}

		const wrappers = document.querySelectorAll( '.amount.lsx-currencies' );
		console.log( LOG, 'price wrappers found:', wrappers.length );

		wrappers.forEach( function ( wrapper, i ) {
			const valueEl = wrapper.querySelector( '.value' );
			if ( ! valueEl ) {
				console.warn( LOG, 'wrapper[' + i + '] has no .value child', wrapper.outerHTML );
				return;
			}

			const dataKey = 'data-price-' + targetCurrency;
			const rawAttr = wrapper.getAttribute( dataKey ) || valueEl.getAttribute( dataKey );
			let converted;

			if ( rawAttr !== null && rawAttr !== '' ) {
				converted = parseFloat( rawAttr );
				console.log( LOG, 'wrapper[' + i + '] pre-stored price for', targetCurrency, '=', converted );
			} else {
				const basePriceAttr = 'data-price-' + params.base;
				const basePrice = parseFloat(
					wrapper.getAttribute( basePriceAttr ) ||
					valueEl.getAttribute( basePriceAttr ) ||
					0
				);
				console.log( LOG, 'wrapper[' + i + '] base price (' + params.base + '):', basePrice,
					'— fx rates set?', Object.keys( fx.rates || {} ).length > 0 );
				try {
					converted = fx( basePrice ).from( params.base ).to( targetCurrency );
					console.log( LOG, 'wrapper[' + i + '] converted', basePrice, params.base, '→', targetCurrency, '=', converted );
				} catch ( e ) {
					console.error( LOG, 'fx conversion error', e );
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
	// -------------------------------------------------------------------------

	function buildLabelHTML( code, showSymbol ) {
		let html = '<span class="wp-block-navigation-item__label">' + code;
		if ( showSymbol && params.symbols && params.symbols[ code ] ) {
			html += ' <span class="lsx-currency-symbol" aria-hidden="true">' + params.symbols[ code ] + '</span>';
		}
		html += '</span>';
		return html;
	}

	function updateSwitcher( switcherLi, newCurrency, oldCurrent ) {
		const showSymbol = switcherLi.dataset.showSymbol === '1';

		const topContent = switcherLi.querySelector( ':scope > .wp-block-navigation-item__content' );
		if ( topContent ) {
			topContent.innerHTML = buildLabelHTML( newCurrency, showSymbol );
			topContent.setAttribute( 'href', '#' + newCurrency.toLowerCase() );
		}

		const submenu = switcherLi.querySelector( ':scope > .wp-block-navigation__submenu-container' );
		if ( ! submenu ) {
			return;
		}

		const toRemove = submenu.querySelector( 'a[data-lsx-currency="' + newCurrency + '"]' );
		if ( toRemove ) {
			toRemove.closest( 'li' )?.remove();
		}

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
			attachItemHandler( switcherLi, a );
		}
	}

	// Track the live current currency across all switchers.
	let activeCurrency = sanitiseCurrencyCode(
		getCookie( 'lsx_currencies_choice' ) || params.current || params.base
	);

	function switchCurrency( newCode ) {
		const code = sanitiseCurrencyCode( newCode );
		console.log( LOG, 'switchCurrency()', { requested: newCode, sanitised: code, active: activeCurrency } );

		if ( ! code || code === activeCurrency ) {
			console.log( LOG, 'no-op — already active or invalid code' );
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
			console.log( LOG, 'submenu item clicked', { code, href: linkEl.href } );
			if ( code ) {
				switchCurrency( code );
				switcherLi.classList.remove( 'is-menu-open' );
				const toggle = switcherLi.querySelector( '.wp-block-navigation-submenu__toggle' );
				if ( toggle && toggle.getAttribute( 'aria-expanded' ) === 'true' ) {
					toggle.click();
				}
			}
		} );
	}

	function attachSwitcherHandlers( switcherLi ) {
		const links = switcherLi.querySelectorAll( 'a[data-lsx-currency]' );
		console.log( LOG, 'attachSwitcherHandlers — links found:', links.length, switcherLi );
		links.forEach( function ( link ) {
			attachItemHandler( switcherLi, link );
		} );
	}

	// -------------------------------------------------------------------------
	// Initialise
	// -------------------------------------------------------------------------

	function init() {
		console.log( LOG, 'init()', {
			fxLoaded: typeof fx !== 'undefined',
			accountingLoaded: typeof accounting !== 'undefined',
		} );

		if ( typeof fx !== 'undefined' ) {
			fx.base  = 'USD';
			fx.rates = params.rates || {};
			console.log( LOG, 'fx configured — base: USD, rates:', Object.keys( fx.rates ).length, 'currencies' );
		}

		const stored = getCookie( 'lsx_currencies_choice' );
		if ( stored ) {
			activeCurrency = sanitiseCurrencyCode( stored );
		}
		console.log( LOG, 'activeCurrency on init:', activeCurrency );

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
