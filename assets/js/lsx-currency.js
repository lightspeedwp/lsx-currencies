var base_currency = 'USD';
var lsx_Money = fx.noConflict();
LSX_Currency = {
	initThis: function() {
		//Set the money rates and the base,   we will always be converting first from the base.
		lsx_Money.rates           = lsx_currency_params.rates;
		lsx_Money.base            = 'USD';

		this.current_currency = lsx_currency_params.current_currency;

		console.log(lsx_Money);
		/*if('USD' !== lsx_currency_params.base){
			this.baseConvert();
		}
		this.checkAmounts();*/

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

	baseConvert: function() {
		var $this = this;
		//set the base to be 

		jQuery('.amount.lsx-currency').each(function() {
			
			var amount = jQuery(this).find('.value').html();
			console.log(amount);
			var new_price = $this.switchCurrency(lsx_currency_params.base,'USD',amount);
			console.log(new_price);

			jQuery(this).find('.value').html(new_price);
			jQuery(this).find('.currency-icon').removeClass(lsx_currency_params.base.toLowerCase()).addClass('usd').html('USD');
		});		
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
			$this.current_currency = jQuery(this).attr('href').replace('#','').toUpperCase();
			$this.checkAmounts(from,$this.current_currency);
		});
	},	

};

jQuery(document).ready( function() {
	LSX_Currency.initThis();
});