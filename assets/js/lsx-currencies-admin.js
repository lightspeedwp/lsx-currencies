/*
 * lsx-currencies-admin.js
 */

jQuery(document).ready(function() {
	
	/*
	 * Watch Select
	 */
	jQuery(document).on('change', '.lsx-select-trigger select', function(e) {
		e.preventDefault();
		e.stopPropagation();

		var $this = jQuery(this),
			name = $this.attr('name'),
			value = $this.val();
		
		jQuery('[data-trigger="' + name + '"] li.hidden input[checked="checked"]').removeAttr("checked").parents('li').show().removeClass('hidden');
		jQuery('[data-trigger="' + name + '"] input[name="additional_currencies[' + value + ']"]').attr('checked','checked').parents('li').hide().addClass('hidden');
	});

	/*
	 * Watch Checkbox
	 */
	/*jQuery('.lsx-checkbox-action input').change(function(e){
		e.preventDefault();
		e.stopPropagation();

		var $this = jQuery(this),
			name = $this.attr('data-name'),
			value = $this.attr('data-value');
		
		jQuery('[data-trigger="'+name+'"] option[selected="selected"]').removeAttr('selected');
		jQuery('[data-trigger="'+name+'"] option[value="'+value+'"]').attr('selected','selected');
	});*/

	/*
	 * Subtabs navigation
	 */
	if (undefined === window.lsx_thumbnail_subtabs_nav) {
		jQuery(document).on('click', '.ui-tab-nav a', function(e) {
			e.preventDefault();
			e.stopPropagation();

			var $this = jQuery(this);

			jQuery('.ui-tab-nav a.active').removeClass('active');
			$this.addClass('active');
			jQuery('.ui-tab.active').removeClass('active');
			jQuery($this.attr('href')).addClass('active');
			
			return false;
		});
		
		window.lsx_thumbnail_subtabs_nav = true;
	}

});
