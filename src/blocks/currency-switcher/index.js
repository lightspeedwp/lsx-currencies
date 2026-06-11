/**
 * Currency Switcher Block — Editor
 *
 * Renders a static navigation-submenu preview inside the Navigation block editor.
 * The live switcher is server-rendered via render.php.
 *
 * @package LSX Currencies
 */

import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import Inspector from './inspector';
import blockConfig from './block.json';
import './style.scss';

/** Static currencies used for the editor preview only. */
const PREVIEW = [
	{ code: 'USD', flag: 'us' },
	{ code: 'EUR', flag: 'eu' },
	{ code: 'GBP', flag: 'gb' },
];

/** SVG caret — identical to core/navigation-submenu. */
const Caret = () => (
	<svg
		xmlns="http://www.w3.org/2000/svg"
		width="12"
		height="12"
		viewBox="0 0 12 12"
		fill="none"
		role="presentation"
		aria-hidden="true"
		focusable="false"
	>
		<path d="M1.50002 4L6.00002 8L10.5 4" strokeWidth="1.5" />
	</svg>
);

/**
 * Render a currency label with optional flag and/or symbol.
 *
 * @param {Object} props
 * @param {string}  props.code          ISO 4217 code.
 * @param {string}  props.flag          Flag-icon suffix.
 * @param {boolean} props.displayFlags  Show flag sprite.
 * @param {string}  props.flagPosition  'left' | 'right'.
 * @param {boolean} props.showSymbol    Show symbol span.
 * @return {JSX.Element}
 */
function CurrencyLabel( { code, flag, displayFlags, flagPosition, showSymbol } ) {
	const flagSpan = displayFlags && (
		<span className={ `flag-icon flag-icon-${ flag }` } aria-hidden="true" />
	);

	return (
		<>
			{ displayFlags && flagPosition === 'left' && flagSpan }
			{ showSymbol && <span className="lsx-currency-symbol" aria-hidden="true" /> }
			<span className="wp-block-navigation-item__label">{ code }</span>
			{ displayFlags && flagPosition === 'right' && flagSpan }
		</>
	);
}

/**
 * Edit component — shows a navigation-submenu-style preview.
 *
 * @param {Object} props
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Attribute setter.
 * @return {JSX.Element}
 */
function Edit( { attributes, setAttributes } ) {
	const { displayFlags, flagPosition, showSymbol } = attributes;

	const [ current, ...rest ] = PREVIEW;

	const blockProps = useBlockProps( {
		className: 'wp-block-navigation-item has-child open-on-hover-click',
	} );

	const labelProps = { displayFlags, flagPosition, showSymbol };

	return (
		<>
			<Inspector attributes={ attributes } setAttributes={ setAttributes } />

			<li { ...blockProps }>
				{ /* Top-level item */ }
				<a className="wp-block-navigation-item__content" href={ `#${ current.code.toLowerCase() }` }>
					<CurrencyLabel code={ current.code } flag={ current.flag } { ...labelProps } />
				</a>
				<button
					className="wp-block-navigation__submenu-icon wp-block-navigation-submenu__toggle"
					aria-label={ __( 'Currency switcher', 'lsx-currencies' ) }
					aria-expanded="false"
				>
					<Caret />
				</button>

				{ /* Submenu preview */ }
				<ul className="wp-block-navigation__submenu-container">
					{ rest.map( ( currency ) => (
						<li key={ currency.code } className="wp-block-navigation-item wp-block-navigation-link">
							<a className="wp-block-navigation-item__content" href={ `#${ currency.code.toLowerCase() }` }>
								<CurrencyLabel code={ currency.code } flag={ currency.flag } { ...labelProps } />
							</a>
						</li>
					) ) }
				</ul>

				<p className="lsx-currencies-editor-note">
					{ __( 'Live currencies are configured in Tour Operator → Currencies settings.', 'lsx-currencies' ) }
				</p>
			</li>
		</>
	);
}

registerBlockType( blockConfig.name, {
	edit: Edit,
	save: () => null, // Server-side rendered via render.php
} );
