<?php
/**
 * LSX Currency Main Class
 */
class LSX_Currency_Admin extends LSX_Currency{	

	/**
	 * Constructor
	 */
	public function __construct() {
		
		add_action('lsx_framework_dashboard_tab_content',array($this,'settings'));

	}
	/**
	 * outputs the dashboard tabs settings
	 */
	public function settings() {
	?>	
		<tr class="heading-wrap">
			<th scope="row">
				<label for="modules"> Currency</label>
			</th>
		</tr>	
		<tr class="form-field-wrap">
			<th scope="row">
				<label for="currency"> Base Currency</label>
			</th>
			<td>
				<select value="{{currency}}" name="currency">
					<option value="USD" {{#is currency value=""}}selected="selected"{{/is}} {{#is currency value="USD"}} selected="selected"{{/is}}>USD (united states dollar)</option>
					<option value="GBP" {{#is currency value="GBP"}} selected="selected"{{/is}}>GBP (british pound)</option>
					<option value="ZAR" {{#is currency value="ZAR"}} selected="selected"{{/is}}>ZAR (south african rand)</option>
					<option value="NAD" {{#is currency value="NAD"}} selected="selected"{{/is}}>NAD (namibian dollar)</option>
					<option value="CAD" {{#is currency value="CAD"}} selected="selected"{{/is}}>CAD (canadian dollar)</option>
					<option value="EUR" {{#is currency value="EUR"}} selected="selected"{{/is}}>EUR (euro)</option>
					<option value="HKD" {{#is currency value="HKD"}} selected="selected"{{/is}}>HKD (hong kong dollar)</option>
					<option value="SGD" {{#is currency value="SGD"}} selected="selected"{{/is}}>SGD (singapore dollar)</option>
					<option value="NZD" {{#is currency value="NZD"}} selected="selected"{{/is}}>NZD (new zealand dollar)</option>
					<option value="AUD" {{#is currency value="AUD"}} selected="selected"{{/is}}>AUD (australian dollar)</option>
				</select>
			</td>
		</tr>
		<tr class="form-field-wrap">
			<th scope="row">
				<label for="modules"> Modules</label>
			</th>
			<td><ul>
			<?php 	

			if(is_array($this->post_types) && !empty($this->post_types)){

				foreach($this->post_types as $slug => $label){
					if('envira' === $slug){ continue; }
					?>
					<li>
						<input type="checkbox" <?php if(in_array($slug,$this->active_post_types)){ echo 'checked="checked"'; } ?> name="post_types[<?php echo $slug; ?>]" /> <label for="post_types"><?php echo $label; ?></label> 
					</li>
				<?php }
			}else{
				?>
					<li>
						You have no modules active. 
					</li>
				<?php
			}
			?>
			</ul></td>
		</tr>		
		?>
	}
}
new LSX_Currency_Admin();