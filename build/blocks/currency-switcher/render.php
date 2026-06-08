<?php
/**
 * Currency Switcher Block — Server-side render callback.
 *
 * Variables available from register_block_type():
 *   $attributes  (array)  Block attributes.
 *   $content     (string) Inner block content (unused — no inner blocks).
 *   $block       (WP_Block) Block instance.
 *
 * @package LSX Currencies
 */

if ( ! function_exists( 'lsx_currencies' ) ) {
	return;
}

$currencies         = lsx_currencies()->additional_currencies;
$base_currency      = lsx_currencies()->base_currency;
$flag_relations     = lsx_currencies()->flag_relations;
$display_flags      = ! empty( $attributes['displayFlags'] );
$flag_position      = isset( $attributes['flagPosition'] ) ? sanitize_key( $attributes['flagPosition'] ) : 'left';
$symbol_position    = isset( $attributes['symbolPosition'] ) ? sanitize_key( $attributes['symbolPosition'] ) : 'right';
$layout             = isset( $attributes['layout'] ) ? sanitize_key( $attributes['layout'] ) : 'horizontal';
$show_current_only  = ! empty( $attributes['showCurrentOnly'] );

// Always include the base currency in the switcher list.
$all_currencies = array( $base_currency => $base_currency );
if ( ! empty( $currencies ) && is_array( $currencies ) ) {
	$all_currencies = array_merge( $all_currencies, $currencies );
}

if ( count( $all_currencies ) < 2 ) {
	// Nothing to switch — render nothing.
	return;
}

// Determine the current currency from cookie.
$current_currency = $base_currency;
if ( isset( $_COOKIE['lsx_currencies_choice'] ) ) {
	$current_currency = strtoupper( sanitize_key( $_COOKIE['lsx_currencies_choice'] ) );
}

// Build wrapper classes.
$wrapper_classes = array(
	'lsx-currency-switcher',
	'lsx-layout-' . esc_attr( $layout ),
);
if ( $show_current_only ) {
	$wrapper_classes[] = 'lsx-show-current-only';
}

$wrapper_attributes = get_block_wrapper_attributes( array(
	'class'     => implode( ' ', $wrapper_classes ),
	'aria-label' => esc_attr__( 'Currency Switcher', 'lsx-currencies' ),
) );
?>
<nav <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() is safe. ?>>

	<?php if ( $show_current_only ) : ?>
		<button class="lsx-currency-toggle" aria-expanded="false" aria-haspopup="listbox">
			<?php
			$flag_code = strtolower( isset( $flag_relations[ $current_currency ] ) ? $flag_relations[ $current_currency ] : '' );
			if ( $display_flags && $flag_code && $flag_position === 'left' ) :
				?>
				<span class="flag-icon flag-icon-<?php echo esc_attr( $flag_code ); ?>" aria-hidden="true"></span>
			<?php endif; ?>
			<span class="lsx-currency-label"><?php echo esc_html( $current_currency ); ?></span>
			<?php
			if ( $display_flags && $flag_code && $flag_position === 'right' ) :
				?>
				<span class="flag-icon flag-icon-<?php echo esc_attr( $flag_code ); ?>" aria-hidden="true"></span>
			<?php endif; ?>
			<span class="lsx-caret" aria-hidden="true">&#9660;</span>
		</button>
	<?php endif; ?>

	<ul class="lsx-currency-list" role="listbox" aria-label="<?php esc_attr_e( 'Available currencies', 'lsx-currencies' ); ?>">
		<?php foreach ( $all_currencies as $code => $code_val ) :
			$code      = strtoupper( sanitize_key( $code ) );
			$is_active = ( $code === $current_currency );
			$flag_key  = isset( $flag_relations[ $code ] ) ? $flag_relations[ $code ] : '';

			$item_classes = array( 'lsx-currency-item' );
			if ( $is_active ) {
				$item_classes[] = 'lsx-currency-current';
			}
			?>
			<li class="<?php echo esc_attr( implode( ' ', $item_classes ) ); ?>" role="option" <?php echo $is_active ? 'aria-selected="true"' : 'aria-selected="false"'; ?>>
				<a href="#<?php echo esc_attr( strtolower( $code ) ); ?>"<?php echo $is_active ? ' aria-current="true"' : ''; ?>>

					<?php if ( $display_flags && $flag_key && $flag_position === 'left' ) : ?>
						<span class="flag-icon flag-icon-<?php echo esc_attr( $flag_key ); ?>" aria-hidden="true"></span>
					<?php endif; ?>

					<?php if ( $symbol_position === 'left' ) : ?>
						<span class="lsx-currency-symbol lsx-symbol-<?php echo esc_attr( strtolower( $code ) ); ?>" aria-hidden="true"></span>
					<?php endif; ?>

					<span class="lsx-currency-label"><?php echo esc_html( $code ); ?></span>

					<?php if ( $symbol_position === 'right' ) : ?>
						<span class="lsx-currency-symbol lsx-symbol-<?php echo esc_attr( strtolower( $code ) ); ?>" aria-hidden="true"></span>
					<?php endif; ?>

					<?php if ( $display_flags && $flag_key && $flag_position === 'right' ) : ?>
						<span class="flag-icon flag-icon-<?php echo esc_attr( $flag_key ); ?>" aria-hidden="true"></span>
					<?php endif; ?>

				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>
