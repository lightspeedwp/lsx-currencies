<?php
/**
 * LSX Currency Main Class
 */
class LSX_Currencies_Admin extends LSX_Currencies{	

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->set_defaults();
		add_action( 'init', array( $this, 'create_settings_page'), 200 );

		add_filter( 'lsx_price_field_pattern', array( $this, 'fields' ), 10, 1 );
	}

	/**
	 * Returns the array of settings to the UIX Class
	 */
	public function create_settings_page() {
		if ( is_admin() ) {
			if ( ! class_exists( '\lsx\ui\uix' ) && !class_exists( 'Tour_Operator' ) ) {
				include_once LSX_CURRENCY_PATH.'vendor/uix/uix.php';
				$pages = $this->settings_page_array();
				$uix = \lsx\ui\uix::get_instance( 'lsx' );
				$uix->register_pages( $pages );
			}

			if ( class_exists( 'Tour_Operator' ) ) {
				add_action( 'to_framework_dashboard_tab_content', array( $this, 'general_settings' ), 20,1 );
				add_action( 'to_framework_display_tab_content', array( $this, 'display_settings' ), 20 );
				add_action( 'to_framework_dashboard_tab_bottom', array( $this, 'settings_scripts' ), 200 );
				add_action( 'to_framework_api_tab_content', array( $this, 'api_settings' ), 20,1 );
			} else {
				add_action( 'lsx_framework_dashboard_tab_content', array( $this, 'general_settings' ), 20 );
				add_action( 'lsx_framework_dashboard_tab_content', array( $this, 'display_settings' ), 20 );
				add_action( 'lsx_framework_dashboard_tab_bottom', array( $this, 'settings_scripts' ), 200 );
			}
		}
	}

	/**
	 * Returns the array of settings to the UIX Class
	 */
	public function settings_page_array() {
		// This array is for the Admin Pages. each element defines a page that is seen in the admin
		
		$tabs = array( // tabs array are for setting the tab / section templates
			// each array element is a tab with the key as the slug that will be the saved object property
			'general' => array(
				'page_title'        => '',
				'page_description'  => '',
				'menu_title'        => esc_html__( 'General', 'lsx-currencies' ),
				'template'          => LSX_CURRENCY_PATH . 'includes/settings/general.php',
				'default'           => true
			)
		);

		return array(
			'lsx-settings'  => array(                                              // this is the settings array. The key is the page slug
				'page_title'  =>  esc_html__( 'LSX Settings', 'lsx-currencies' ),  // title of the page
				'menu_title'  =>  esc_html__( 'LSX Settings', 'lsx-currencies' ),  // title seen on the menu link
				'capability'  =>  'manage_options',                                // required capability to access page
				'icon'        =>  'dashicons-book-alt',                            // Icon or image to be used on admin menu
				'parent'      =>  'options-general.php',                           // Position priority on admin menu)
				'save_button' =>  esc_html__( 'Save Changes', 'lsx-currencies' ),  // If the page required saving settings, Set the text here.
				'tabs'        =>  $tabs,
			),
		);
	}

	/**
	 * Outputs the dashboard tabs settings
	 *
	 * @param $tab string
	 * @return null
	 */
	public function api_settings($tab='general')
	{
		if ('api' !== $tab) {
			$this->currency_api_heading();
			$this->api_key_field();
		}
	}

	/**
	 * Outputs the dashboard tabs settings
	 *
	 * @param $tab string
	 * @return null
	 */
	public function general_settings($tab='general') {
		if ( class_exists( 'Tour_Operator' ) && 'currency_switcher' !== $tab ) { return false; }
			if ( !class_exists( 'Tour_Operator' )) {
				$this->currency_heading();
				$this->api_key_field();
			}
			$this->base_currency_field();
			$this->additional_currencies_field();
			$this->enable_multiple_prices_field();
	}

	/**
	 * Outputs the display tabs settings
	 *
	 * @param $tab string
	 * @return null
	 */
	public function display_settings($tab='general') {
		if ( class_exists( 'Tour_Operator' ) && 'currency_switcher' !== $tab ) { return false; }
		if ( !class_exists( 'Tour_Operator' )){
			$this->currency_switcher_heading();
		}
		$this->display_in_menu_field();
		$this->display_flags_field();
		$this->flag_position_field();
		$this->symbol_position_field();
	}
	/**
	 * Outputs the currency heading
	 */
	public function currency_api_heading() { ?>
		<tr class="form-field banner-wrap">
			<th class="table_heading" style="padding-bottom:0px;" scope="row" colspan="2">
				<h4 style="margin-bottom:0px;"><?php esc_html_e('LSX Currencies','lsx-currencies'); ?></h4>
			</th>
		</tr>
	<?php }

	/**
	 * Outputs the currency heading
	 */
	public function currency_heading($tag='h3') { ?>
		<tr class="form-field banner-wrap">
			<th class="table_heading" style="padding-bottom:0px;" scope="row" colspan="2">
				<h3 style="margin-bottom:0px;"><label><?php esc_html_e('Currency Settings','lsx-currencies'); ?></label></h3>
			</th>
		</tr>
	<?php }
	/**
	 * Outputs the api key text field
	 */
	public function api_key_field() { ?>
		<tr class="form-field">
			<th scope="row">
				<i class="dashicons-before dashicons-admin-network"></i><label for="openexchange_api"> <?php _e( 'Key', 'to-maps' ); ?></label>
			</th>
			<td>
				<input type="text" {{#if openexchange_api}} value="{{openexchange_api}}" {{/if}} name="openexchange_api" />
				<br /><small><?php esc_html_e('Get your free API key here','lsx-currencies'); ?> - <a target="_blank" href="https://openexchangerates.org/signup/free">openexchangerates.org</a></small>
			</td>
		</tr>
	<?php }
	/**
	 * Outputs the base currency drop down
	 */
	public function base_currency_field() { ?>
		<tr data-trigger="additional_currencies" class="lsx-select-trigger form-field-wrap">
			<th scope="row">
				<label for="currency"><?php esc_html_e('Base Currency','lsx-currencies');?></label>
			</th>
			<td>
				<select value="{{currency}}" name="currency">
					<?php
					foreach($this->available_currencies as $currency_id => $currency_label ){

						$selected = '';
						if($currency_id === $this->base_currency){
							$selected='selected="selected"';
						}
						echo '<option value="'.$currency_id.'" '.$selected.'>'.$currency_label.'</option>';
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
				<label for="modules"><?php esc_html_e('Additional Currencies','lsx-currencies');?></label>
			</th>
			<td><ul>
					<?php
					foreach($this->available_currencies as $slug => $label){
						$checked = $hidden = '';
						if(array_key_exists($slug,$this->additional_currencies) || $slug === $this->base_currency){
							$checked='checked="checked"';
						}

						if($slug === $this->base_currency){
							$hidden = 'style="display:none;" class="hidden"';
						}
						?>
						<li <?php echo $hidden; ?>>
							<input type="checkbox" <?php echo $checked; ?> data-name="additional_currencies" data-value="<?php echo $slug; ?>" name="additional_currencies[<?php echo $slug; ?>]" /> <label for="additional_currencies"><?php echo $this->get_currency_flag($slug).$label; ?></label>
						</li>
					<?php }
					?>
				</ul></td>
		</tr>
	<?php }
	/**
	 * Outputs the multiple prices checkbox
	 */
	public function enable_multiple_prices_field() { ?>
		<tr class="form-field">
			<th scope="row">
				<label for="multi_price"><?php esc_html_e('Enable Multiple Prices','lsx-currencies'); ?></label>
			</th>
			<td>
				<input type="checkbox" {{#if multi_price}} checked="checked" {{/if}} name="multi_price" />
				<small><?php esc_html_e('Allowing you to add specific prices per active currency.','lsx-currencies'); ?></small>
			</td>
		</tr>
	<?php }
	/**
	 * Outputs the currency switcher heading
	 */
	public function currency_switcher_heading() { ?>
		<tr class="form-field banner-wrap">
			<th class="table_heading" style="padding-bottom:0px;" scope="row" colspan="2">
				<label><h3 style="margin-bottom:0px;"><?php esc_html_e('Currency Switcher','lsx-currencies'); ?></h3></label>
			</th>
		</tr>
	<?php }
	/**
	 * Outputs the symbol position field
	 */
	public function display_in_menu_field() { ?>
		<tr class="form-field-wrap">
			<th scope="row">
				<label for="currency_menu_switcher"><?php esc_html_e('Display in Menu','lsx-currencies'); ?></label>
			</th>
			<td><ul>
					<?php
					$all_menus = get_registered_nav_menus();
					if(is_array($all_menus) && !empty($all_menus)){
						foreach($all_menus as $slug => $label){
							$checked = $hidden = '';
							if(is_array($this->menus) && array_key_exists($slug,$this->menus)){
								$checked='checked="checked"';
							}
							?>
							<li>
								<input type="checkbox" <?php echo $checked; ?> name="currency_menu_switcher[<?php echo $slug; ?>]" /> <label for="additional_currencies"><?php echo $label; ?></label>
							</li>
						<?php }
					}else{
						echo '<li><p>'.esc_html__('You have no menus set up.','lsx-currencies').'</p></li>';
					}
					?>
				</ul></td>
		</tr>
	<?php }
	/**
	 * Outputs the Display flags checkbox
	 */
	public function display_flags_field() { ?>
		<tr class="form-field">
			<th scope="row">
				<label for="display_flags"><?php esc_html_e('Display Flags','lsx-currencies'); ?></label>
			</th>
			<td>
				<input type="checkbox" {{#if display_flags}} checked="checked" {{/if}} name="display_flags" />
				<small><?php esc_html_e('Displays a small flag in front of the name.','lsx-currencies'); ?></small>
			</td>
		</tr>
	<?php }
	/**
	 * Outputs the flag position field
	 */
	public function flag_position_field() { ?>
		<tr class="form-field">
			<th scope="row">
				<label for="flag_position"><?php esc_html_e('Flag Position','lsx-currencies'); ?></label>
			</th>
			<td>
				<input type="checkbox" {{#if flag_position}} checked="checked" {{/if}} name="flag_position" />
				<small><?php esc_html_e('This moves the flag to the right (after the symbol).','lsx-currencies'); ?></small>
			</td>
		</tr>
	<?php }
	/**
	 * Outputs the symbol position field
	 */
	public function symbol_position_field() { ?>
		<tr class="form-field">
			<th scope="row">
				<label for="currency_switcher_position"><?php esc_html_e('Symbol Position','lsx-currencies'); ?></label>
			</th>
			<td>
				<input type="checkbox" {{#if currency_switcher_position}} checked="checked" {{/if}} name="currency_switcher_position" />
				<small><?php esc_html_e('This moves the symbol for the switcher to the left (before the flag).','lsx-currencies'); ?></small>
			</td>
		</tr>
	<?php }



	/**
	 * Outputs the dashboard tabs settings scripts
	 */
	public function settings_scripts() {
		?>
		<script>
			var LSX_Select_Checkbox = {
				initThis: function() {
					if('undefined' != jQuery('.lsx-select-trigger') && 'undefined' != jQuery('.lsx-checkbox-action') ){
						this.watchSelect();
						//this.watchCheckbox();
					}
				},
				watchSelect: function() {
					jQuery('.lsx-select-trigger select').change(function(event){
						event.preventDefault();
						var name = jQuery(this).attr('name');
						var value = jQuery(this).val();
						jQuery('[data-trigger="'+name+'"] li.hidden input[checked="checked"]').removeAttr("checked").parents('li').show().removeClass('hidden');
						jQuery('[data-trigger="'+name+'"] input[name="additional_currencies['+value+']"]').attr('checked','checked').parents('li').hide().addClass('hidden');
					});
				},
				watchCheckbox: function() {
					jQuery('.lsx-checkbox-action input').change(function(event){
						event.preventDefault();
						var name = jQuery(this).attr('data-name');
						var value = jQuery(this).attr('data-value');
						console.log(value);

						jQuery('[data-trigger="'+name+'"] option[selected="selected"]').removeAttr('selected');
						jQuery('[data-trigger="'+name+'"] option[value="'+value+'"]').attr('selected','selected');
					});					
				}
			};	
			jQuery(document).ready(function() {
				LSX_Select_Checkbox.initThis();
			});
		</script>
		<?php
	}	



	/**
	 * 
	 */
	public function fields( $field ) {
		if(true === $this->multi_prices && !empty($this->additional_currencies)){
			$currency_options = array();
			foreach($this->additional_currencies as $key => $values){
				if($key === $this->base_currency){continue;}
				$currency_options[$key] = $this->available_currencies[$key];
			}

			return array(
				array( 'id' => 'price_title',  'name' => esc_html__('Prices','lsx-currencies'), 'type' => 'title' ),
				array( 'id' => 'price',  'name' => 'Base Price ('.$this->base_currency.')', 'type' => 'text' ),
				array(
					'id' => 'additional_prices',
					'name' => '',
					'single_name' => 'Price',
					'type' => 'group',
					'repeatable' => true,
					'sortable' => true,
					'fields' => array(
						array( 'id' => 'amount',  'name' => 'Amount', 'type' => 'text' ),
						array( 'id' => 'currency', 'name' => 'Currency', 'type' => 'select', 'options' => $currency_options ),
					)
				)			
			);
		}else{
			return array(array( 'id' => 'price',  'name' => 'Price ('.$this->base_currency.')', 'type' => 'text' ));
		}	
	}	
}
