<?php
namespace Longman\TelegramBot;

use Exception;
use Longman\TelegramBot\Exception\TelegramException;

/**
* Api requests class
*/
class ApiRequests 
{	
	/**
	 * Call needed function to check if currency exists 
	 * @param  [string] $exchange [exchange name]
	 * @param  [string] $currency [currency name]
	 * @return [bool]
	 */
		public static function checkCurrencyName($exchange, $currency)
		{	
			# build needed function's name
			$function = 'check' . ucfirst($exchange) . 'Currency';

			return self::$function($currency);
		}

	/**
	 * Check if there such currency on Binance exchange
	 * @param  [string] $exchange [currency name]
	 * @return [bool]
	 */
		public static function checkBinanceCurrency($exchange)
		{	
			if ($exchange == 'BTC' || $exchange == 'USDT') {
				return true;
			}

			# Binance get url
			$url = 'https://api.binance.com/api/v3/ticker/price?symbol=' . $exchange . 'BTC';
			
			$response = @file_get_contents($url);
			
			/** return true if there are no errors */
				if ($response != false) {
					$response = json_decode($response);
					
					/** check if there is error's code number */
						if (isset($response->code)) {	
							return false;
						}

					return true;
				} else {
					return false;
				}
	 	}

	/**
	 * Call needed functions to check currencies' prices
	 * @param [array] $currencies_arr [info about user's coins]
	 * @param [string] $type [usdt/btc comparison]
	 */
		public static function getPrices($holdings, $type)
		{	
			$portfolio = '';
			$binance_arr = [];
			$poloniex_arr = [];

			for ($i = 0; $i < count($holdings); $i++) { 
				switch ($holdings[$i]['exchange_name']) {
				 	case 'binance':
				 		array_push($binance_arr, $holdings[$i]);
				 		break;
					// case 'poloniex': 	
				} 
			}

			if (!empty($binance_arr)) {
				$portfolio .= 'Exchange: Binance' . PHP_EOL . self::getBinancePrices($binance_arr, $type);
			}

			return $portfolio;
		}

	/**
	 * Get binance prices 
	 * @param [array] $currencies_arr [info about user's coins]
	 * @param [string] $type [usdt/btc comparison]
	 */
		public static function getBinancePrices($currencies_arr, $type)
		{	
			# Binance get url
			$url = 'https://api.binance.com/api/v1/ticker/24hr?symbol=';
			$portfolio = '';

			# currencies, that haven't got currency/USDT pair
			$usdt_empty = [];

			/** get BTC/USDT price */
				// if ($type == 'usdt') {	
					$btc = $url . 'BTCUSDT';
					$btc_usdt =  @file_get_contents($btc);
					$btc_usdt = json_decode($btc_usdt);
					$btc_change = doubleval($btc_usdt->priceChangePercent);
					$btc_usdt = doubleval($btc_usdt->weightedAvgPrice);
				// }


			/** add binance prices to answer */
				for ($i = 0; $i < count($currencies_arr); $i++) {
					$price = 0;
					$change = '';

					/** count Bitcoin's holdings */
						if ($currencies_arr[$i]['currency_name'] == 'BTC') {
							$price = $btc_usdt * $currencies_arr[$i]['currency_amount'];

							/** set visual design to price changes*/
								if ($btc_change > 0) {
									$change .= ', +' . $btc_change . '% 💚';
								} else {
									$change .=  ', ' . $btc_change . '% ❤️';
								}
						} 
					/** count USDT holdings */
						elseif ($currencies_arr[$i]['currency_name'] == 'USDT') {
							$price = $currencies_arr[$i]['currency_amount'];
							$change = '';
						}

					/** get current coin's price */
						if ($price == 0) {
							/** get current currency price */
								$response = @file_get_contents($url.$currencies_arr[$i]['currency_name'].$type);
								$response_arr = json_decode($response);
							
							/** skip iteration if current currency hasn't got currency/USDT */
								if ($response == false && $type == 'USDT' || isset($response->code)) {
									array_push($usdt_empty, $currencies_arr[$i]);
									continue;
								}
							
							/** count current currency price */
								$price = doubleval($response_arr->weightedAvgPrice) * $currencies_arr[$i]['currency_amount'];
								$price = round($price, 3);

							/** set 24hr currencies' price change */
								$price_change = round(doubleval($response_arr->priceChangePercent), 2);

							/** set visual design to price changes*/
								if ($price_change > 0) {
									$change .= ', +' . $price_change . '% 💚';
								} else {
									$change .=  ', ' . $price_change . '% ❤️';
								}

						}
						/** set message row */
							$portfolio .= $currencies_arr[$i]['currency_name'] . ': ' . $currencies_arr[$i]['currency_amount'] . ' (' . $price . '$' .  $change . ')'. PHP_EOL;
				}

				/** count prices of the currencies which haven't got currency/USDT pair */
					# algorithm:
					# get currency/BTC price
					# currency/BTC price * 1 BTC's price
					print_r($usdt_empty);
					if (!empty($usdt_empty)) {
						foreach ($usdt_empty as $currency) {
							$coin_btc = $url . $currency['currency_name'] . 'BTC';
							$response =  @file_get_contents($coin_btc);
							$response_arr = json_decode($response);
							
							if ($response == false || isset($response_arr->code)) {
								continue;
							}
							
							$price = doubleval($response_arr->weightedAvgPrice) * $btc_usdt * $currency['currency_amount'];
							$price = round($price, 3);

							/** set 24hr currencies' price change */
								$price_change = round(doubleval($response_arr->priceChangePercent), 2);
								$change = '';

							/** set visual design to price changes*/
								if ($price_change > 0) {
									$change .= '+' . $price_change . '% 💚';
								} else {
									$change .=  $price_change . '% ❤️';
								}

							/** set message row */
								$portfolio .= '(BTC->USDT) ' . $currency['currency_name'] . ': ' . $currency['currency_amount'] . ' (' . $price . '$, ' .  $change . ')'. PHP_EOL;
						}
					}

			return $portfolio;
		}
}

?>