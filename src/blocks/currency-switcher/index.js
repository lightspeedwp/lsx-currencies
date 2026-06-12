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

/** Static currencies used for the editor preview only. */
const PREVIEW = [ 'USD', 'EUR', 'GBP' ];

/** SVG caret — identical to core/navigation-submenu. */
const Caret = () => (
	<svg
		xmlns="http://www.w3.org/2000/svg"
		width="12"
		height="12"
		viewBox="0 0 12 12"
		fill="none"
		aria-hidden="true"
		focusable="false"
	>
		<path d="M1.50002 4L6.00002 8L10.5 4" strokeWidth="1.5" />
	</svg>
);

/**
 * Edit component — shows a navigation-submenu-style preview.
 *
 * @param {Object} props
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Attribute setter.
 * @return {JSX.Element}
 */
function Edit( { attributes, setAttributes } ) {
	const [ current, ...rest ] = PREVIEW;

	const blockProps = useBlockProps( {
		className: 'wp-block-navigation-item has-child open-on-hover-click',
	} );

	return (
		<>
			<Inspector attributes={ attributes } setAttributes={ setAttributes } />

			<li { ...blockProps }>
				<a className="wp-block-navigation-item__content" href={ `#${ current.toLowerCase() }` }>
					<span className="wp-block-navigation-item__label">{ current }</span>
				</a>
				<button
					className="wp-block-navigation__submenu-icon wp-block-navigation-submenu__toggle"
					aria-label={ __( 'Currency switcher', 'lsx-currencies' ) }
					aria-expanded="false"
				>
					<Caret />
				</button>

				<ul className="wp-block-navigation__submenu-container">
					{ rest.map( ( code ) => (
						<li key={ code } className="wp-block-navigation-item wp-block-navigation-link">
							<a className="wp-block-navigation-item__content" href={ `#${ code.toLowerCase() }` }>
								<span className="wp-block-navigation-item__label">{ code }</span>
							</a>
						</li>
					) ) }
				</ul>
			</li>
		</>
	);
}

registerBlockType( blockConfig.name, {
	edit: Edit,
	save: () => null, // Server-side rendered via render.php
} );
