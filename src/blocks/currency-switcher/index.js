/**
 * Currency Switcher Block — Editor
 *
 * @package LSX Currencies
 */

import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import Inspector from './inspector';
import blockConfig from './block.json';
import './style.scss';

/**
 * Edit component.
 */
function Edit( { attributes, setAttributes } ) {
	const { displayFlags, flagPosition, symbolPosition, layout, showCurrentOnly } = attributes;

	const blockProps = useBlockProps( {
		className: `lsx-currency-switcher lsx-layout-${ layout }`,
	} );

	const previewCurrencies = [
		{ code: 'USD', flag: 'us' },
		{ code: 'EUR', flag: 'eu' },
		{ code: 'GBP', flag: 'gb' },
	];

	return (
		<>
			<Inspector
				attributes={ attributes }
				setAttributes={ setAttributes }
			/>
			<nav { ...blockProps } aria-label={ __( 'Currency Switcher', 'lsx-currencies' ) }>
				<ul className="lsx-currency-list">
					{ previewCurrencies.map( ( currency, index ) => (
						<li
							key={ currency.code }
							className={ `lsx-currency-item${ index === 0 ? ' lsx-currency-current' : '' }` }
						>
							<a href={ `#${ currency.code.toLowerCase() }` }>
								{ displayFlags && flagPosition === 'left' && (
									<span className={ `flag-icon flag-icon-${ currency.flag }` } aria-hidden="true" />
								) }
								{ symbolPosition === 'left' && (
									<span className={ `lsx-currency-symbol lsx-symbol-${ currency.code.toLowerCase() }` } aria-hidden="true" />
								) }
								<span className="lsx-currency-label">{ currency.code }</span>
								{ symbolPosition === 'right' && (
									<span className={ `lsx-currency-symbol lsx-symbol-${ currency.code.toLowerCase() }` } aria-hidden="true" />
								) }
								{ displayFlags && flagPosition === 'right' && (
									<span className={ `flag-icon flag-icon-${ currency.flag }` } aria-hidden="true" />
								) }
							</a>
						</li>
					) ) }
				</ul>
				<p className="lsx-currencies-editor-note components-placeholder__instructions">
					{ __( 'Live currencies are configured in Tour Operator → Currencies settings.', 'lsx-currencies' ) }
				</p>
			</nav>
		</>
	);
}

registerBlockType( blockConfig.name, {
	edit: Edit,
	save: () => null, // Server-side rendered via render.php
} );
