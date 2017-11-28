var lsx_money = fx.noConflict();

LSX_Currencies = {
	initThis: function() {
		//console.log('[LSX_Currencies.initThis] lsx_currencies_params.rates_message: ' + lsx_currencies_params.rates_message);

		if ('' === lsx_currencies_params.rates) {
			//console.log('[LSX_Currencies.initThis] no rates, aborting');
			return;
		}

		console.log( lsx_currencies_params );

		//Set the money rates and the base, we will always be converting first from the base.
		lsx_money.rates = lsx_currencies_params.rates;
		lsx_money.base = 'USD';

		this.current_currency = lsx_currencies_params.current_currency;

		//If the user has a previous selection, then change the amounts to that base
		if (this.current_currency != lsx_currencies_params.base) {
			this.checkAmounts(lsx_currencies_params.base);
		}

		this.watchMenuSwitcher();
	},

	checkAmounts: function(from) {
		//console.log('[LSX_Currencies.checkAmounts] from: ' + from);

		var $this = this;

		jQuery('.amount.lsx-currencies').each(function() {

            var amount = '',
                new_price = '',
                strict_amount = '';

			if ( jQuery( this ).hasClass( 'woocommerce-Price-amount') ) {
                strict_amount = jQuery(this).attr('data-price-' + $this.current_currency);
                amount = jQuery(this).attr('data-price-' + lsx_currencies_params.base);
			} else {
                strict_amount = jQuery(this).find('.value').attr('data-price-' + $this.current_currency);
                amount = jQuery(this).find('.value').attr('data-price-' + lsx_currencies_params.base);
			}

            if (typeof strict_amount !== typeof undefined && strict_amount !== false) {
                new_price = strict_amount;
            } else {
                new_price = $this.switchCurrency(lsx_currencies_params.base, $this.current_currency, amount );
            }

            if ( jQuery( this ).hasClass( 'woocommerce-Price-amount') ) {

				var currency_symbol = $this.current_currency;
				if ( undefined !== lsx_currencies_params.currency_symbols[ $this.current_currency ] ) {
                    currency_symbol = lsx_currencies_params.currency_symbols[ $this.current_currency ];
				}

				var currency_span = '<span class="woocommerce-Price-currencySymbol">' + currency_symbol + '</span>' + new_price;
                jQuery(this).html(currency_span);
            } else {
                jQuery(this).find('.value').html(new_price);
                jQuery(this).find('.currency-icon').prop('class', '').addClass('currency-icon').addClass($this.current_currency.toLowerCase()).html($this.current_currency);

			}

        });
	},

	switchCurrency: function(from, to, amount) {
		//console.log('[LSX_Currencies.switchCurrency] from: ' + from);
		//console.log('[LSX_Currencies.switchCurrency] to: ' + to);
		//console.log('[LSX_Currencies.switchCurrency] lsx_currencies_params:');
		//console.log(lsx_currencies_params);

		//If the current from price is not the base
		amount = lsx_money(amount).from(from).to(to);
		amount = this.formatAmount(amount);
		return amount;
	},

	formatAmount: function(amount) {
		amount = accounting.formatNumber(amount, 2, ',', '.');
		return amount;
	},

	watchMenuSwitcher: function() {
		var $this = this;

		jQuery('.menu-item-currency a').on('click',function(event) {
			event.preventDefault();
			from = $this.current_currency;
			var currency_class = jQuery(this).attr('href').replace('#', '');
			$this.current_currency = currency_class.toUpperCase();

			//Find the UL submenu from which ever button was clicked, and insert a new currency option.
			var selector = '';

			if (!jQuery(this).hasClass('current')) {
				//Check if the currency flag should display
				var currency_flag = '';

				if (true == lsx_currencies_params.flags) {
					currency_flag = '<span class="flag-icon flag-icon-' + currency_class.substring(0, 2) + '"></span> ';
				}

				//move the new labels up to the current selector
				jQuery(this).parents('li.menu-item-currency-current').find('a.current').attr('href', '#' + currency_class).html(currency_flag + $this.current_currency + '<span class="currency-icon ' + currency_class + '"></span><span class="caret"></span>');

				//show the old selection from the drop down and
				jQuery(this).parents('li.menu-item-currency-current').find('li.hidden').show().removeClass('hidden');

				//Hide the new one
				jQuery(this).parent().hide().addClass('hidden');
			}

			//Set the COokie with your currency selection
			Cookies.set('lsx_currencies_choice', $this.current_currency);

			//Cycle through the divs and convert the amounts.
			$this.checkAmounts(from, $this.current_currency);
		});
	},

	menuLabelToggle: function(amount) {
		amount = accounting.formatNumber(amount, 2, ',', '.');
		return amount;
	}

};

jQuery(document).ready( function() {
	LSX_Currencies.initThis();
});
