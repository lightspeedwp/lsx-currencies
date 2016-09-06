var lsx_Money = fx.noConflict();
LSX_Currency = {
	initThis: function() {
		//Set the money rates and the base,   we will always be converting first from the base.
		lsx_Money.rates           = lsx_currency_params.rates;
		lsx_Money.base            = 'USD';

		this.current_currency = lsx_currency_params.current_currency;

		//If the user has a previous selection, then change the amounts to that base
		if(this.current_currency != lsx_currency_params.base){
			this.checkAmounts(lsx_currency_params.base);
		}
		this.watchMenuSwitcher();
	},

	checkAmounts: function(from) {
		var $this = this;
		if('undefined' != jQuery('.amount.lsx-currency')){
			jQuery('.amount.lsx-currency').each(function() {

				var amount = '';

				var basePrice = jQuery(this).find('.value').attr('data-base-price');
				if (typeof basePrice !== typeof undefined && basePrice !== false) {
					amount = basePrice;
					from = lsx_currency_params.base;
				}else{
					amount = jQuery(this).find('.value').html();
					jQuery(this).find('.value').attr('data-base-price',amount);
				}
				var new_price = $this.switchCurrency(from,$this.current_currency,amount);

				jQuery(this).find('.value').html(new_price);
				jQuery(this).find('.currency-icon').removeClass(from.toLowerCase()).addClass($this.current_currency.toLowerCase()).html($this.current_currency);				
			});
		}
	},	

	switchCurrency: function(from,to,amount) {
		console.log(from,to);
		amount = lsx_Money(amount).from(from).to(to);
		amount = this.formatAmount(amount);
		return amount;
	},	

	formatAmount: function(amount) {
		amount = accounting.formatNumber(amount,2,',','.');	
		return amount;	
	},

	watchMenuSwitcher: function() {
		var $this = this;
		jQuery('.menu-item-currency a').on('click',function(event) {
			event.preventDefault();
			from = $this.current_currency;
			var currency_class = jQuery(this).attr('href').replace('#','');
			$this.current_currency = currency_class.toUpperCase();

			//Find the UL submenu from which ever button was clicked, and insert a new currency option.
			var selector = '';
			if(!jQuery(this).hasClass('current')){
				//move the new labels up to the current selector
				jQuery(this).parents('li.menu-item-currency-current')
					.find('a.current').attr('href','#'+currency_class)
					.html($this.current_currency+'<span class="currency-icon '+currency_class+'"></span><span class="caret"></span>');
				//show the old selection from the drop down and 
				jQuery(this).parents('li.menu-item-currency-current').find('li.hidden').show().removeClass('hidden');
				//Hide the new one
				jQuery(this).parent().hide().addClass('hidden');
			}

				//Set the COokie with your currency selection
			Cookies.set('lsx_currency_choice', $this.current_currency);
			//Cycle through the divs and convert the amounts.
			$this.checkAmounts(from,$this.current_currency);
		});
	},

	menuLabelToggle: function(amount) {
		amount = accounting.formatNumber(amount,2,',','.');	
		return amount;	
	},		

};

jQuery(document).ready( function() {
	LSX_Currency.initThis();
});