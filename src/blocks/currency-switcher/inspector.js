/**
 * Currency Switcher Block — Inspector Controls
 *
 * @package LSX Currencies
 */

import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Inspector sidebar controls for the Currency Switcher block.
 *
 * @param {Object}   props               Component props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Function to update attributes.
 * @return {JSX.Element} Inspector panel.
 */
export default function Inspector( { attributes, setAttributes } ) {
	const { showSymbol } = attributes;

	return (
		<InspectorControls>
			<PanelBody title={ __( 'Appearance', 'lsx-currencies' ) } initialOpen={ true }>
				<ToggleControl
					label={ __( 'Show Currency Symbol', 'lsx-currencies' ) }
					help={ __( 'Display the currency symbol (e.g. $, €) alongside the code.', 'lsx-currencies' ) }
					checked={ showSymbol }
					onChange={ ( value ) => setAttributes( { showSymbol: value } ) }
				/>
			</PanelBody>
		</InspectorControls>
	);
}
