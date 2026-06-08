/**
 * Currency Switcher Block — Inspector Controls
 *
 * @package LSX Currencies
 */

import { InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	ToggleControl,
	SelectControl,
} from '@wordpress/components';
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
	const { displayFlags, flagPosition, symbolPosition, layout, showCurrentOnly } = attributes;

	return (
		<InspectorControls>
			<PanelBody title={ __( 'Appearance', 'lsx-currencies' ) } initialOpen={ true }>
				<SelectControl
					label={ __( 'Layout', 'lsx-currencies' ) }
					value={ layout }
					options={ [
						{ label: __( 'Horizontal', 'lsx-currencies' ), value: 'horizontal' },
						{ label: __( 'Vertical', 'lsx-currencies' ), value: 'vertical' },
					] }
					onChange={ ( value ) => setAttributes( { layout: value } ) }
				/>

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

				<SelectControl
					label={ __( 'Currency Symbol Position', 'lsx-currencies' ) }
					value={ symbolPosition }
					options={ [
						{ label: __( 'After code', 'lsx-currencies' ), value: 'right' },
						{ label: __( 'Before code', 'lsx-currencies' ), value: 'left' },
						{ label: __( 'Hidden', 'lsx-currencies' ), value: 'none' },
					] }
					onChange={ ( value ) => setAttributes( { symbolPosition: value } ) }
				/>

				<ToggleControl
					label={ __( 'Show Current Currency Only', 'lsx-currencies' ) }
					help={ __( 'Collapses to a single item that expands on click.', 'lsx-currencies' ) }
					checked={ showCurrentOnly }
					onChange={ ( value ) => setAttributes( { showCurrentOnly: value } ) }
				/>
			</PanelBody>
		</InspectorControls>
	);
}
