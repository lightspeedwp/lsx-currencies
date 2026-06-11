/**
 * Currency Switcher Block — Inspector Controls
 *
 * @package LSX Currencies
 */

import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl, SelectControl } from '@wordpress/components';
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
	const { displayFlags, flagPosition, showSymbol } = attributes;

	return (
		<InspectorControls>
			<PanelBody title={ __( 'Appearance', 'lsx-currencies' ) } initialOpen={ true }>
				<ToggleControl
					label={ __( 'Show Currency Flags', 'lsx-currencies' ) }
					checked={ displayFlags }
					onChange={ ( value ) => setAttributes( { displayFlags: value } ) }
				/>

				{ displayFlags && (
					<SelectControl
						label={ __( 'Flag Position', 'lsx-currencies' ) }
						value={ flagPosition }
						options={ [
							{ label: __( 'Left of label', 'lsx-currencies' ), value: 'left' },
							{ label: __( 'Right of label', 'lsx-currencies' ), value: 'right' },
						] }
						onChange={ ( value ) => setAttributes( { flagPosition: value } ) }
					/>
				) }

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
