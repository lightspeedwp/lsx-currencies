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
function lsx_currencies_get_price_html( $value = '', $post_id = false, $args = array() ) {
	$prefix   = '<span class="amount lsx-currencies" ';
	$value    = preg_replace( '/[^0-9.]+/', '', $value );
	$decimals = substr_count( $value, '.' );
	if ( false === $post_id ) {
		$post_id = get_the_ID();
	}

	$defaults = array(
		'currency_tag' => true,
	);
	$args = wp_parse_args( $args, $defaults );

	$money_format = 2;
	if ( false !== lsx_currencies()->remove_decimals ) {
		$money_format = 0;
	}

	if ( false !== $decimals && $decimals > 1 ) {
		$decimals--;
		$decimals = (int) $decimals;
		$value = preg_replace( '/' . preg_quote( '.', '/' ) . '/', '', $value, $decimals );
	}
	$prefix .= '>';
	$suffix = '</span>';
	setlocale( LC_MONETARY, 'en_US' );

	// Set the prices to use the base currency set on the tour.
	$currency      = lsx_currencies()->base_currency;
	$tour_currency = get_post_meta( $post_id, 'currency', true );
	if ( false !== $tour_currency && '' !== $tour_currency ) {
		$currency = strtoupper( $tour_currency );
	}

	// Work out the other tags
	$currency_tag = '';
	if ( true === $args['currency_tag'] ) {
		$currency_tag = '<span class="currency-icon ' . mb_strtolower( $currency ) . '">' . $currency . '</span>';
	}	

	$formatted_amount = number_format( (float) $value, $money_format );
	$formatted_amount = str_replace( array( '$', 'USD' ), '', $formatted_amount );

	$amount = '<span class="value" data-price-' . $currency . '="' . trim( str_replace( 'USD', '', $formatted_amount ) ) . '">' . str_replace( 'USD', '', $formatted_amount ) . '</span>';
	$price_html = '<span class="amount lsx-currencies">' . $currency_tag . $amount . '</span>';
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
