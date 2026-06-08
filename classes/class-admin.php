<?php
/**
 * LSX Currencies Admin Class
 *
 * Integrates with the Tour Operator settings page using the same hooks and
 * field-registration pattern as lsx\admin\Settings:
 *
 *  - `lsx_to_settings_fields`                  → registers fields so they are
 *                                                 saved by TO's save_settings().
 *  - `lsx_to_framework_dashboard_tab_content`  → renders fields inside the
 *                                                 General settings page table.
 *
 * @package   LSX Currencies
 * @author    LightSpeed
 * @license   GPL3
 * @copyright 2024 LightSpeed
 */

namespace lsx\currencies\classes;

/**
 * Registers currency fields into the Tour Operator settings page.
 */
class Admin {

	/**
	 * Singleton instance.
	 *
	 * @var \lsx\currencies\classes\Admin
	 */
	private static $instance;

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Add our fields to the TO save loop so they are persisted in lsx_to_settings.
		add_filter( 'lsx_to_settings_fields', array( $this, 'add_settings_fields' ) );

		// Register render hooks inside create_settings_page, mirroring the TO pattern.
		add_action( 'admin_menu', array( $this, 'create_settings_page' ), 110 );
	}

	/**
	 * Return singleton instance.
	 *
	 * @return \lsx\currencies\classes\Admin
	 */
	public static function init() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register render hooks for the TO settings page sections.
	 * Mirrors the pattern used in lsx\admin\Settings::create_settings_page().
	 */
	public function create_settings_page() {
		if ( ! is_admin() ) {
			return;
		}

		// Render additional currency checkboxes in the 'currency' section (priority 20,
		// after TO's own currency fields at priority 11).
		add_action( 'lsx_to_framework_dashboard_tab_content', array( $this, 'currency_settings' ), 20, 1 );

		// Render OpenExchangeRates API key in the 'api' section (priority 20,
		// after TO's Google Maps key at priority 15).
		add_action( 'lsx_to_framework_dashboard_tab_content', array( $this, 'api_settings' ), 20, 1 );
	}

	/**
	 * Inject our fields into the TO settings-fields array so they are included
	 * in save_settings() and written to lsx_to_settings.
	 *
	 * Using type 'custom' for lsx_currencies_additional means output_fields()
	 * hits the default: branch and renders nothing — we render it ourselves.
	 * The remaining fields (checkbox / text) are rendered by output_fields()
	 * automatically when TO processes the 'currency' and 'api' sections.
	 *
	 * @param array $fields Existing settings fields.
	 * @return array
	 */
	public function add_settings_fields( array $fields ) {
		// ── Currency section ──────────────────────────────────────────────────
		$fields['currency']['lsx_currencies_additional'] = array(
			'label'   => esc_html__( 'Additional Currencies', 'lsx-currencies' ),
			'type'    => 'custom', // rendered manually; output_fields() skips unknown types
			'default' => '',
		);

		$fields['currency']['lsx_currencies_multi_price'] = array(
			'label'   => esc_html__( 'Enable Multiple Prices', 'lsx-currencies' ),
			'desc'    => esc_html__( 'Allow per-currency pricing on tour posts via the additional_prices meta field.', 'lsx-currencies' ),
			'type'    => 'checkbox',
			'default' => 0,
		);

		$fields['currency']['lsx_currencies_convert_to_single'] = array(
			'label'   => esc_html__( 'Convert to Single Currency', 'lsx-currencies' ),
			'desc'    => esc_html__( 'Convert all prices to the base currency using live exchange rates.', 'lsx-currencies' ),
			'type'    => 'checkbox',
			'default' => 0,
		);

		$fields['currency']['lsx_currencies_remove_decimals'] = array(
			'label'   => esc_html__( 'Remove Decimals', 'lsx-currencies' ),
			'desc'    => esc_html__( 'Round all displayed prices to the nearest whole number.', 'lsx-currencies' ),
			'type'    => 'checkbox',
			'default' => 0,
		);

		// ── API section ───────────────────────────────────────────────────────
		$fields['api']['lsx_currencies_openexchange_api'] = array(
			'label'   => esc_html__( 'OpenExchangeRates API Key', 'lsx-currencies' ),
			'desc'    => esc_html__( 'Optional. Unlocks additional currencies. Leave blank to use the free exchange-rate endpoint.', 'lsx-currencies' ),
			'type'    => 'text',
			'default' => '',
		);

		return $fields;
	}

	/**
	 * Renders the additional-currencies multi-checkbox field in the 'currency'
	 * section of the TO General settings tab.
	 *
	 * The checkboxes update a hidden <input name="lsx_currencies_additional"> with
	 * a comma-separated string so that TO's save_settings() can pick it up via
	 * sanitize_text_field( $_POST['lsx_currencies_additional'] ).
	 *
	 * @param string $tab Current section identifier.
	 */
	public function currency_settings( $tab ) {
		if ( 'currency' !== $tab ) {
			return;
		}

		$options     = get_option( 'lsx_to_settings', array() );
		$saved_raw   = isset( $options['lsx_currencies_additional'] ) ? $options['lsx_currencies_additional'] : '';
		$saved_codes = array_filter( array_map( 'sanitize_key', explode( ',', $saved_raw ) ) );
		$currencies  = lsx_currencies()->get_available_currencies();
		?>
		<tr class="form-field lsx_currencies_additional">
			<th scope="row"><?php esc_html_e( 'Additional Currencies', 'lsx-currencies' ); ?></th>
			<td>
				<input type="hidden"
					name="lsx_currencies_additional"
					id="lsx_currencies_additional_value"
					value="<?php echo esc_attr( $saved_raw ); ?>">

				<div class="lsx-currencies-checkboxes">
					<?php foreach ( $currencies as $code => $label ) :
						$code    = sanitize_key( $code );
						$checked = in_array( $code, $saved_codes, true ) ? 'checked="checked"' : '';
						?>
						<label style="display:inline-block;margin:0 12px 8px 0;">
							<input type="checkbox"
								class="lsx-currency-check"
								data-code="<?php echo esc_attr( strtoupper( $code ) ); ?>"
								<?php echo $checked; ?>>
							<?php echo esc_html( strtoupper( $code ) . ' &mdash; ' . $label ); ?>
						</label>
					<?php endforeach; ?>
				</div>
				<br>
				<small><?php esc_html_e( 'Currencies visitors can switch to using the Currency Switcher block.', 'lsx-currencies' ); ?></small>

				<script>
				( function () {
					var hidden = document.getElementById( 'lsx_currencies_additional_value' );
					if ( ! hidden ) { return; }
					document.querySelectorAll( '.lsx-currency-check' ).forEach( function ( cb ) {
						cb.addEventListener( 'change', function () {
							var codes = [];
							document.querySelectorAll( '.lsx-currency-check:checked' ).forEach( function ( c ) {
								codes.push( c.dataset.code );
							} );
							hidden.value = codes.join( ',' );
						} );
					} );
				} )();
				</script>
			</td>
		</tr>
		<?php
	}

	/**
	 * Renders the OpenExchangeRates API key in the 'api' section.
	 * The field is also registered in add_settings_fields() as type 'text', so
	 * TO's output_fields() renders it automatically — this hook is a no-op kept
	 * for parity with the TO pattern (useful if custom rendering is needed later).
	 *
	 * @param string $tab Current section identifier.
	 */
	public function api_settings( $tab ) {
		// The 'lsx_currencies_openexchange_api' text field is already rendered by
		// TO's output_fields() at priority 15. Nothing extra needed here.
		return;
	}
}
