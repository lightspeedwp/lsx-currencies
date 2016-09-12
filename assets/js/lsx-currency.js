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
				var new_price = '';

				var strict_amount = jQuery(this).find('.value').attr('data-price-'+$this.current_currency);
				console.log(strict_amount);
				if (typeof strict_amount !== typeof undefined && strict_amount !== false) {
					new_price = strict_amount;
				}else{
					new_price = $this.switchCurrency(lsx_currency_params.base,$this.current_currency,jQuery(this).find('.value').attr('data-price-'+lsx_currency_params.base));
				}
				jQuery(this).find('.value').html(new_price);
				console.log(from);
				jQuery(this).find('.currency-icon').prop('class',"").addClass('currency-icon').addClass($this.current_currency.toLowerCase()).html($this.current_currency);				
			});
		}
	},	

	switchCurrency: function(from,to,amount) {
		console.log(from,to);
		console.log(lsx_currency_params);

		//if the current from price is not the base

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

				//Check if the currency flag should display
				var currency_flag = '';
				if('undefined' != jQuery(this).find('span.flag-icon')){
					currency_icon = '<span class="flag-icon flag-icon-'+currency_class.substring(0, 2)+'"></span> ';
				}
				
				//move the new labels up to the current selector
				jQuery(this).parents('li.menu-item-currency-current').find('a.current').attr('href','#'+currency_class).html(currency_icon+$this.current_currency+'<span class="currency-icon '+currency_class+'"></span><span class="caret"></span>');
				
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