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
						if(in_array($currency_id,$this->additional_currencies)){
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
				if(in_array($slug,$this->additional_currencies) || $slug === $this->base_currency){
					$checked='checked="checked"';
				}
				
				if($slug === $this->base_currency){
					$hidden = 'style="display:none;"';
				}
				?>
				<li <?php echo $hidden; ?>>
					<input type="checkbox" <?php echo $checked; ?> data-name="additional_currencies" data-value="<?php echo $slug; ?>" name="additional_currencies[<?php echo $slug; ?>]" /> <label for="additional_currencies"><?php echo $label; ?></label> 
				</li>
			<?php }
			?>
			</ul></td>
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
						jQuery('[data-trigger="'+name+'"] input[checked="checked"]').removeAttr("checked").parents('li').show();
						jQuery('[data-trigger="'+name+'"] input[name="additional_currencies['+value+']"]').attr('checked','checked').parents('li').hide();
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
}
new LSX_Currency_Admin();