var base_currency = 'USD';
LSX_Currency = {
	initThis: function() {
		if('undefined' != jQuery('.amount.lsx-currency')){
			this.watchLink();
			base_currency = jQuery('.amount.lsx-currency').attr('data-base-currency');
		}
	},
	watchLink: function() {
		jQuery('#itinerary .view-more a').click(function(event){
			event.preventDefault();
			jQuery(this).hide();
			
			jQuery(this).parents('#itinerary').find('.itinerary-item.hidden').each(function(){
				jQuery(this).removeClass('hidden');
			});
		});
	}
};

jQuery(document).ready( function() {
	LSX_Currency.initThis();
});