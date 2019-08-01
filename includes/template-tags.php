<?php
/**
 * SCPO Engine
 *
 * @package   LSX Testimonials
 * @author    LightSpeed
 * @license   GPL3
 * @link
 * @copyright 2018 LightSpeed
 */

/**
 * Wraps your price in the currency html
 *
 * @param string $value
 * @return void
 */
function lsx_currencies_get_price_html( $value = '' ) {
	$prefix = '<span class="amount lsx-currencies" ';
	$value = preg_replace( '/[^0-9.]+/', '', $value );
	$decimals = substr_count( $value, '.' );

	if ( false !== $decimals && $decimals > 1 ) {
		$decimals--;
		$decimals = (int) $decimals;
		$value = preg_replace( '/' . preg_quote( '.', '/' ) . '/', '', $value, $decimals );
	}
	$prefix .= '>';
	$suffix = '</span>';
	setlocale( LC_MONETARY, 'en_US' );

	// Work out the other tags
	$currency = '<span class="currency-icon ' . mb_strtolower( lsx_currencies()->base_currency ) . '">' . lsx_currencies()->base_currency . '</span>';
	$amount = '<span class="value" data-price-' . lsx_currencies()->base_currency . '="' . trim( str_replace( 'USD', '', money_format( '%i', ltrim( rtrim( $value ) ) ) ) ) . '">' . str_replace( 'USD', '', money_format( '%i', ltrim( rtrim( $value ) ) ) ) . '</span>';
	$price_html = '<span class="amount lsx-currencies">' . $currency . $amount . '</span>';
	return $price_html;
}

/**
 * A shortcode to wrap a value in your content
 *
 * @param array $atts
 * @return void
 */
function lsx_currency_value( $atts ) {
	$a = shortcode_atts(
		array(
			'value' => '0.00',
		),
		$atts
	);
	return lsx_currencies_get_price_html( $a['value'] );
}
add_shortcode( 'lsx_currency_value', 'lsx_currency_value' );
