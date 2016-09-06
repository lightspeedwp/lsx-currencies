<?php
/**
 * LSX Currency Main Class
 */
class LSX_Currency_Admin extends LSX_Currency{	

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->set_defaults();
		add_action('lsx_framework_dashboard_tab_content',array($this,'settings'),11);
		add_action('lsx_framework_dashboard_tab_bottom',array($this,'settings_scripts'),11);
		
		add_filter('lsx_price_field_pattern',array($this,'fields'),10,1);
	}
	/**
	 * outputs the dashboard tabs settings
	 */
	public function settings() {
	?>	
		<tr class="form-field banner-wrap">
			<th class="table_heading" style="padding-bottom:0px;" scope="row" colspan="2">
			<label><h3 style="margin-bottom:0px;"><?php _e('Currency',$this->plugin_slug); ?></h3></label>			
			</th>
		</tr>
		<tr data-trigger="additional_currencies" class="lsx-select-trigger form-field-wrap">
			<th scope="row">
				<label for="currency"><?php _e('Base Currency',$this->plugin_slug);?></label>
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
		<tr data-trigger="currency" class="lsx-checkbox-action form-field-wrap">
			<th scope="row">
				<label for="modules"><?php _e('Additional Currencies',$this->plugin_slug);?></label>
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
					<input type="checkbox" <?php echo $checked; ?> data-name="additional_currencies" data-value="<?php echo $slug; ?>" name="additional_currencies[<?php echo $slug; ?>]" /> <label for="additional_currencies"><?php echo $label; ?></label> 
				</li>
			<?php }
			?>
			</ul></td>
		</tr>
		<tr class="form-field">
			<th scope="row">
				<label for="multi_price"><?php _e('Enable Multiple Prices',$this->plugin_slug); ?></label>
			</th>
			<td>
				<input type="checkbox" {{#if multi_price}} checked="checked" {{/if}} name="multi_price" />
				<small><?php _e('Allowing you to add specific prices per active currency.',$this->plugin_slug); ?></small>
			</td>
		</tr>		
		<?php	
	}

	/**
	 * outputs the dashboard tabs settings scripts
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
	 * outputs the dashboard tabs settings
	 */
	public function fields($field) {
		if(true === $this->multi_prices && !empty($this->additional_currencies)){
			$currency_options = array();
			foreach($this->additional_currencies as $key => $values){
				$currency_options[$key] = $this->available_currencies[$key];
			}

			return array(
				array( 'id' => 'price_title',  'name' => __('Prices',$this->plugin_slug), 'type' => 'title' ),
				array( 'id' => 'price',  'name' => 'Base Price', 'type' => 'text' ),
				array(
						'id' => 'additional_prices',
						'name' => '',
						'single_name' => 'Additional Prices',
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
			return $field;
		}	
	}	
}
new LSX_Currency_Admin();