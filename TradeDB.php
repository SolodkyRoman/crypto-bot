<?php
namespace Longman\TelegramBot;

use Exception;
use Longman\TelegramBot\Exception\TelegramException;
use PDO;

/**
 * Interactions with holdings' tables
 */
class TradeDB extends DB
{	
	/**
	 * Update user's holding
	 * @param  [string] $chat_id  [chat id]
	 * @param  [string] $exchange [exchange name]
	 * @param  [currency] $currency [currency name]
	 * @param  [string] $amount   [amount of the currency]
	 * @return [bool]
	 */
	public static function updateHoldings($chat_id, $exchange, $currency, $amount)
	{	
		/** change string values on numbers */
			$chat_id = intval($chat_id);
			$amount = floatval($amount);
		
		if ($chat_id === null && $exchange === null && $currency == null && $amount == null) {
            throw new TelegramException('Missing data');
        }

        if (!self::isDbConnected()) {
            return false;
        }

        /** delete old info about current currency */
	        try {
	        	$sth = self::$pdo->prepare('
	                DELETE FROM `users_holdings`
	                WHERE `chat_id` = :chat_id AND `exchange_name` = :exchange_name AND `currency_name` = :currency_name
	            ');

	            $sth->bindValue(':chat_id', $chat_id);
	            $sth->bindValue(':exchange_name', $exchange);
	            $sth->bindValue(':currency_name', $currency);

	            $sth->execute();
	        } catch (PDOException $e) {
	            throw new TelegramException($e->getMessage());
	        }

	    /** insert new info about current currency */
	        try {
	            $sth = self::$pdo->prepare('
	                INSERT IGNORE INTO `users_holdings`
	                (`chat_id`, `exchange_name`, `currency_name`, `currency_amount`)
	                VALUES
	                (:chat_id, :exchange_name, :currency_name, :currency_amount)
	            ');

	            $sth->bindValue(':chat_id', $chat_id);
	            $sth->bindValue(':exchange_name', $exchange);
	            $sth->bindValue(':currency_name', $currency);
	            $sth->bindValue(':currency_amount', $amount);

	            return $sth->execute();
	        } catch (PDOException $e) {
	            throw new TelegramException($e->getMessage());
	        }
	}

	/**
	 * Check if needed exchange exists 
	 * @param  [string] $exchange [exchange's name]
	 * @return [bool]
	 */
	public static function checkExchangeName($exchange)
	{
		if (!self::isDbConnected()) {
            return false;
        }

        try {
            $sth = self::$pdo->prepare('
                SELECT `name`
                FROM `exchanges`
                WHERE `name` = :exchange
                LIMIT :limit
            ');

            $sth->bindValue(':exchange', $exchange);
            $sth->bindValue(':limit', 1, PDO::PARAM_INT);
            
            $sth->execute();
       		$exchange_name = $sth->fetchAll(PDO::FETCH_ASSOC); 	
        
        	/** if there is such exchange in the database return true  */
	           	if ($sth->rowCount() != 0) {
	           		return true;
	           	} else {
	           		return false;
	           	}
           
        } catch (PDOException $e) {
            throw new TelegramException($e->getMessage());
        }
	}

	/**
	 * Select user's holdings from the database
	 */
	public static function selectUsersHoldings($chat_id)
	{
		if (!self::isDbConnected()) {
            return false;
        }

        if ($chat_id === null) {
            throw new TelegramException('chat_id is mmissing');
        }

          try {
            $sth = self::$pdo->prepare('
                SELECT *
                FROM `users_holdings`
                WHERE `chat_id` = :chat_id
                ORDER BY :order
            ');

            $sth->bindValue(':chat_id', $chat_id);
            $sth->bindValue(':order', 'exchange_name');
            
            $sth->execute();
       		$holdings = $sth->fetchAll(PDO::FETCH_ASSOC); 	
           return $holdings;
        } catch (PDOException $e) {
            throw new TelegramException($e->getMessage());
        }
	}
}
?>