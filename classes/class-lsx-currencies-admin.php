<?php
/**
 * LSX Currency Admin Class
 *
 * @package   LSX Currencies
 * @author    LightSpeed
 * @license   GPL3
 * @link
 * @copyright 2016 LightSpeed
 */
class LSX_Currencies_Admin extends LSX_Currencies {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->set_defaults();

		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );

		add_action( 'init', array( $this, 'create_settings_page' ), 100 );
		add_filter( 'lsx_framework_settings_tabs', array( $this, 'register_tabs' ), 100, 1 );

		add_filter( 'lsx_price_field_pattern', array( $this, 'fields' ), 10, 1 );

		add_action( 'customize_register', array( $this, 'customize_register' ), 20 );
	}

	/**
	 * Enques the assets
	 */
	public function assets() {
		wp_enqueue_script( 'lsx-currencies-admin', LSX_CURRENCIES_URL . 'assets/js/lsx-currencies-admin.min.js', array( 'jquery' ), LSX_CURRENCIES_VER, true );
		wp_enqueue_style( 'lsx-currencies-admin', LSX_CURRENCIES_URL . 'assets/css/lsx-currencies-admin.css', array(), LSX_CURRENCIES_VER );
	}

	/**
	 * Returns the array of settings to the UIX Class
	 */
	public function create_settings_page() {
		if ( is_admin() ) {
			if ( ! class_exists( '\lsx\ui\uix' ) && ! function_exists( 'tour_operator' ) ) {
				include_once LSX_CURRENCIES_PATH . 'vendor/uix/uix.php';
				$pages = $this->settings_page_array();
				$uix = \lsx\ui\uix::get_instance( 'lsx' );
				$uix->register_pages( $pages );
			}

			// @TODO the function_exists( 'tour_operator' ) is not working here.
			//if ( function_exists( 'tour_operator' ) ) {
				add_action( 'lsx_to_framework_dashboard_tab_content', array( $this, 'general_settings' ), 11,1 );
				add_action( 'lsx_to_framework_api_tab_content', array( $this, 'api_settings' ), 11, 1 );
			//} else {
				add_action( 'lsx_framework_dashboard_tab_content', array( $this, 'general_settings' ), 11,1 );
				add_action( 'lsx_framework_api_tab_content', array( $this, 'api_settings' ), 11, 1 );
			//}
		}
	}

	/**
	 * Returns the array of settings to the UIX Class
	 */
	public function settings_page_array() {
		$tabs = apply_filters( 'lsx_framework_settings_tabs', array() );

		return array(
			'settings'  => array(
				'page_title'  => esc_html__( 'Theme Options', 'lsx-currencies' ),
				'menu_title'  => esc_html__( 'Theme Options', 'lsx-currencies' ),
				'capability'  => 'manage_options',
				'icon'        => 'dashicons-book-alt',
				'parent'      => 'themes.php',
				'save_button' => esc_html__( 'Save Changes', 'lsx-currencies' ),
				'tabs'        => $tabs,
			),
		);
	}

	/**
	 * Register tabs
	 */
	public function register_tabs( $tabs ) {
		$default = true;

		if ( false !== $tabs && is_array( $tabs ) && count( $tabs ) > 0 ) {
			$default = false;
		}

		if ( ! function_exists( 'tour_operator' ) ) {
			if ( ! array_key_exists( 'general', $tabs ) ) {
				$tabs['general'] = array(
					'page_title'        => '',
					'page_description'  => '',
					'menu_title'        => esc_html__( 'General', 'lsx-currencies' ),
					'template'          => LSX_CURRENCIES_PATH . 'includes/settings/general.php',
					'default'           => $default,
				);

				$default = false;
			}

			if ( ! array_key_exists( 'display', $tabs ) ) {
				$tabs['display'] = array(
					'page_title'        => '',
					'page_description'  => '',
					'menu_title'        => esc_html__( 'Display', 'lsx-currencies' ),
					'template'          => LSX_CURRENCIES_PATH . 'includes/settings/display.php',
					'default'           => $default,
				);

				$default = false;
			}

			if ( ! array_key_exists( 'api', $tabs ) ) {
				$tabs['api'] = array(
					'page_title'        => '',
					'page_description'  => '',
					'menu_title'        => esc_html__( 'API', 'lsx-currencies' ),
					'template'          => LSX_CURRENCIES_PATH . 'includes/settings/api.php',
					'default'           => $default,
				);

				$default = false;
			}
		}

		return $tabs;
	}

	/**
	 * Outputs the dashboard tabs settings
	 *
	 * @param $tab string
	 * @return null
	 */
	public function general_settings( $tab = 'general' ) {
		if ( 'currency_switcher' === $tab ) {
			$this->base_currency_field();
			$this->additional_currencies_field();
			$this->enable_multiple_prices_field();
		}
	}

	/**
	 * Outputs the dashboard tabs settings
	 *
	 * @param $tab string
	 * @return null
	 */
	public function api_settings( $tab = 'general' ) {
		if ( 'settings' === $tab ) {
			$this->currency_api_heading();
			$this->api_key_field();
		}
	}

	/**
	 * Outputs the base currency drop down
	 */
	public function base_currency_field() { ?>
		<tr data-trigger="additional_currencies" class="lsx-select-trigger form-field-wrap">
			<th scope="row">
				<label for="currency"><?php esc_html_e( 'Base Currency', 'lsx-currencies' );?></label>
			</th>
			<td>
				<select value="{{currency}}" name="currency">
					<?php
					foreach ( $this->available_currencies as $currency_id => $currency_label ) {
						$selected = '';

						if ( $currency_id === $this->base_currency ) {
							$selected = 'selected="selected"';
						}

						echo '<option value="' . $currency_id . '" ' . $selected . '>' . $currency_label . '</option>';
					} ?>
				</select>
			</td>
		</tr>
	<?php }

	/**
	 * Outputs the additional currencies checkboxes
	 */
	public function additional_currencies_field() { ?>
		<tr data-trigger="currency" class="lsx-checkbox-action form-field-wrap">
			<th scope="row">
				<label for="modules"><?php esc_html_e( 'Additional Currencies', 'lsx-currencies' );?></label>
			</th>
			<td>
				<ul>
					<?php
						foreach ( $this->available_currencies as $slug => $label ) {
							$checked = $hidden = '';

							if ( array_key_exists( $slug, $this->additional_currencies ) || $slug === $this->base_currency ) {
								$checked = 'checked="checked"';
							}

							if ( $slug === $this->base_currency ) {
								$hidden = 'style="display:none;" class="hidden"';
							}
							?>
							<li <?php echo $hidden; ?>>
								<input type="checkbox" <?php echo $checked; ?> data-name="additional_currencies" data-value="<?php echo $slug; ?>" name="additional_currencies[<?php echo $slug; ?>]" /> <label for="additional_currencies"><?php echo $this->get_currency_flag( $slug ) . $label; ?></label>
							</li>
							<?php
						}
					?>
				</ul>
			</td>
		</tr>
	<?php }

	/**
	 * Outputs the multiple prices checkbox
	 */
	public function enable_multiple_prices_field() { ?>
		<tr class="form-field">
			<th scope="row">
				<label for="multi_price"><?php esc_html_e( 'Enable Multiple Prices', 'lsx-currencies' ); ?></label>
			</th>
			<td>
				<input type="checkbox" {{#if multi_price}} checked="checked" {{/if}} name="multi_price" />
				<small><?php esc_html_e( 'Allowing you to add specific prices per active currency.', 'lsx-currencies' ); ?></small>
			</td>
		</tr>
	<?php }

	/**
	 * Outputs the currency heading
	 */
	public function currency_api_heading() { ?>
		<tr class="form-field banner-wrap">
			<th class="table_heading" style="padding-bottom:0px;" scope="row" colspan="2">
				<h4 style="margin-bottom:0px;"><?php esc_html_e( 'LSX Currencies', 'lsx-currencies' ); ?></h4>
			</th>
		</tr>
	<?php }

	/**
	 * Outputs the api key text field
	 */
	public function api_key_field() { ?>
		<tr class="form-field">
			<th scope="row">
				<i class="dashicons-before dashicons-admin-network"></i><label for="openexchange_api"> <?php esc_html_e( 'Key', 'to-maps' ); ?></label>
			</th>
			<td>
				<input type="text" {{#if openexchange_api}} value="{{openexchange_api}}" {{/if}} name="openexchange_api" />
				<br /><small><?php esc_html_e( 'Get your free API key here', 'lsx-currencies' ); ?> - <a target="_blank" href="https://openexchangerates.org/signup/free">openexchangerates.org</a></small>
			</td>
		</tr>
	<?php }

	/**
	 *
	 */
	public function fields( $field ) {
		if ( true === $this->multi_prices && ! empty( $this->additional_currencies ) ) {
			$currency_options = array();

			foreach ( $this->additional_currencies as $key => $values ) {
				if ( $key === $this->base_currency ) {
					continue;
				}

				$currency_options[ $key ] = $this->available_currencies[ $key ];
			}

			return array(
				array(
					'id' => 'price_title',
					'name' => esc_html__( 'Prices', 'lsx-currencies' ),
					'type' => 'title',
				),
				array(
					'id' => 'price',
					'name' => 'Base Price (' . $this->base_currency . ')',
					'type' => 'text',
				),
				array(
					'id' => 'additional_prices',
					'name' => '',
					'single_name' => 'Price',
					'type' => 'group',
					'repeatable' => true,
					'sortable' => true,
					'fields' => array(
						array(
							'id' => 'amount',
							'name' => 'Amount',
							'type' => 'text',
						),
						array(
							'id' => 'currency',
							'name' => 'Currency',
							'type' => 'select',
							'options' => $currency_options,
						),
					),
				),
			);
		} else {
			return array(
				array(
					'id' => 'price',
					'name' => 'Price (' . $this->base_currency . ')',
					'type' => 'text',
				),
			);
		}
	}

	/**
	 * Customizer Controls and Settings.
	 *
	 * @since 1.1.1
	 */
	public function customize_register( $wp_customize ) {
		/**
		 * Panel.
		 */

		$wp_customize->add_panel( 'lsx_currencies', array(
			'priority'       	=> 62,
			'capability'     	=> 'edit_theme_options',
			'theme_supports' 	=> '',
			'title'				=> esc_html__( 'Currencies', 'lsx-currencies' ),
			'description'    	=> esc_html__( 'LSX Currencies extension settings.', 'lsx-currencies' ),
		) );

		/**
		 * Section.
		 */

		$wp_customize->add_section( 'lsx_currencies_display' , array(
			'title'       => esc_html__( 'Display', 'lsx-currencies' ),
			'description' => esc_html__( 'LSX Currencies extension display settings.', 'lsx-currencies' ),
			'panel'       => 'lsx_currencies',
			'priority'    => 1,
		) );

		/**
		 * Fields.
		 */

		$wp_customize->add_setting( 'lsx_currencies_currency_menu_position', array(
			'default' => '',
			'sanitize_callback' => array( $this, 'sanitize_select' ),
		) );

		$choices = array(
			'' => esc_html__( 'None', 'lsx-currencies' ),
		);

		$menus = get_registered_nav_menus();

		if ( is_array( $menus ) && ! empty( $menus ) ) {
			$choices = array_merge( $choices, $menus );
		}

		$wp_customize->add_control( new WP_Customize_Control( $wp_customize, 'lsx_currencies_currency_menu_position', array(
			'label'       => esc_html__( 'Display in Menu', 'lsx-currencies' ),
			'description' => esc_html__( 'Select the menu to display the currency menu switcher.', 'lsx-currencies' ),
			'section'     => 'lsx_currencies_display',
			'settings'    => 'lsx_currencies_currency_menu_position',
			'type'        => 'select',
			'priority'    => 1,
			'choices'     => $choices,
		) ) );

		$wp_customize->add_setting( 'lsx_currencies_display_flags', array(
			'default'           => false,
			'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
		) );

		$wp_customize->add_control( new WP_Customize_Control( $wp_customize, 'lsx_currencies_display_flags', array(
			'label'       => esc_html__( 'Display Flags', 'lsx-currencies' ),
			'description' => esc_html__( 'Displays a small flag in front of the name.', 'lsx-currencies' ),
			'section'     => 'lsx_currencies_display',
			'settings'    => 'lsx_currencies_display_flags',
			'type'        => 'checkbox',
			'priority'    => 2,
		) ) );

		$wp_customize->add_setting( 'lsx_currencies_flag_position', array(
			'default'           => 'left',
			'sanitize_callback' => array( $this, 'sanitize_select' ),
		) );

		$wp_customize->add_control( new WP_Customize_Control( $wp_customize, 'lsx_currencies_flag_position', array(
			'label'       => esc_html__( 'Flag Position', 'lsx-currencies' ),
			'description' => esc_html__( 'This moves the flag to the right (after the symbol).', 'lsx-currencies' ),
			'section'     => 'lsx_currencies_display',
			'settings'    => 'lsx_currencies_flag_position',
			'type'        => 'select',
			'priority'    => 3,
			'choices'     => array(
				'left' => esc_html__( 'Left', 'lsx-currencies' ),
				'right' => esc_html__( 'Right', 'lsx-currencies' ),
			),
		) ) );

		$wp_customize->add_setting( 'lsx_currencies_currency_switcher_position', array(
			'default'           => 'right',
			'sanitize_callback' => array( $this, 'sanitize_select' ),
		) );

		$wp_customize->add_control( new WP_Customize_Control( $wp_customize, 'lsx_currencies_currency_switcher_position', array(
			'label'       => esc_html__( 'Symbol Position', 'lsx-currencies' ),
			'description' => esc_html__( 'This moves the symbol for the switcher to the left (before the flag).', 'lsx-currencies' ),
			'section'     => 'lsx_currencies_display',
			'settings'    => 'lsx_currencies_currency_switcher_position',
			'type'        => 'select',
			'priority'    => 4,
			'choices'     => array(
				'left' => esc_html__( 'Left', 'lsx-currencies' ),
				'right' => esc_html__( 'Right', 'lsx-currencies' ),
			),
		) ) );
	}

}
