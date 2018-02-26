<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Entities\PhotoSize;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\TradeDB;
use Longman\TelegramBot\ApiRequests;

/**
 * Edit coin command
 */
class EditcoinCommand extends UserCommand
{	
	/**
	 * Command's name
	 * 
	 * @var string
	 */
	protected $name = 'editcoin';                     
    
	/**
	 * Command's description
	 * @var string
	 */
    protected $description = 'A command for test'; 
    
    /**
     * Usage of command
     * @var string
     */
    protected $usage = '/editcoin';                    
    
    /**
     * Version of command
     * @var string
     */
    protected $version = '1.0.0';                  
   
	/**
	 * @var bool
	*/
    protected $need_mysql = true;
    
    /**
     * @var bool
     */
    protected $private_only = true;
    
    /**
     * Conversation Object
     *
     * @var \Longman\TelegramBot\Conversation
     */
    protected $conversation;

    public function execute()
    {	
      	$message = $this->getMessage();
        
        $chat    = $message->getChat();
        $user    = $message->getFrom();
        $text    = trim($message->getText(true));
        $chat_id = $chat->getId();
        $user_id = $user->getId();
        $holdings = []; 
        
        //Preparing Response
        $data = [
            'chat_id' => $chat_id,
            'text' => ''
        ];
        
        if ($chat->isGroupChat() || $chat->isSuperGroup()) {
            //reply to message id is applied by default
            //Force reply is applied by default so it can work with privacy on
            $data['reply_markup'] = Keyboard::forceReply(['selective' => true]);
        }
       
        //Conversation start
        $this->conversation = new Conversation($user_id, $chat_id, $this->getName());
       
        $notes = &$this->conversation->notes;
        !is_array($notes) && $notes = [];
        
        //cache data from the tracking session if any
        $state = 0;
        if (isset($notes['state'])) {
            $state = $notes['state'];
        }
        $result = Request::emptyResponse();

        //stop conversation
        if ($text == 'Stop') {
        	$this->conversation->stop();
        	$data['reply_markup'] = Keyboard::remove(['selective' => true]);
        	$data['text'] = 'Action canceled';
            $result = Request::sendMessage($data);

            return $result;
        }

        if ($text == 'Back') {
			$state--;        	
        }
    	
      	// Request::sendMessage(['chat_id' => $chat_id]);

        //State machine
        //Entrypoint of the machine state if given by the track
        //Every time a step is achieved the track is updated
        switch ($state) {
            // no break
            case 0:
            	//Check if exchange exists
            	$exchange = false;
            	if ($text !== '') {
	            	$exchange = TradeDB::checkExchangeName($text);
            	}

                if ($text === '' || $text == 'Back' || $exchange == false) {
                    $notes['state'] = 0;
                    $this->conversation->update();
                    
                    //Set action buttons
                    $data['reply_markup'] = (new Keyboard(['Binance', 'Stop']))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);
                    $data['text'] = 'Select your cryptocurrency trading platform:';
                    
                    if ($text !== '') {
                        $data['text'] = 'Select your cryptocurrency trading platform, choose a keyboard option:';
                    }
                    
                    $result = Request::sendMessage($data);
                    break;
                }
                $notes['exchange'] = strtolower($text);
                $text = '';
            // no break
            case 1:
            	//Check if currency exists
            	$currency = false;
            	$text = strtoupper($text);
            	if ($text !== '') {
	            	$currency = ApiRequests::checkCurrencyName($notes['exchange'], $text);
            	}

                if ($text === '' || $text == 'Back' || $currency == false) {
                    $notes['state'] = 1;
                    $this->conversation->update();
                    
                    //Set action buttons
                    $data['reply_markup'] = (new Keyboard(['Back', 'Stop']))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);
                    $data['text'] = 'Type your cryptocurrency:';
                    
                    if ($text !== '' && $text != 'Back') {
                        $data['text'] = 'Type your cryptocurrency, must be an abbreviation:';
                    }
                    
                    $result = Request::sendMessage($data);
                    break;
                }
                $notes['currency'] = $text;
                $text = '';
            // no break
            case 2:
                if ($text === '' || !is_numeric($text)) {
                    $notes['state'] = 2;
                    $this->conversation->update();
                    
                    //Set action buttons
                    $data['reply_markup'] = (new Keyboard(['Back', 'Stop']))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);
                    $data['text'] = 'Type coin amount:';
                    
                    if ($text !== '' && $text != 'Back') {
                        $data['text'] = 'Type coin amount, must be a number:';
                    }
                    
                    $result = Request::sendMessage($data);
                    break;
                }
                $notes['amount'] = $text;
                $text = '';
            case 3:
                $this->conversation->update();
                unset($notes['state']);
              	
              	//Set answer                
                $out_text = 'Holdings are succesfully updated' . PHP_EOL .
                			'Exchange: ' . ucfirst($notes['exchange']) . PHP_EOL .
                			'Currency: ' . $notes['currency'] . PHP_EOL . 
                			'Amount: ' . $notes['amount'];

               	//Update holding info in database
               	TradeDB::updateHoldings($chat_id, $notes['exchange'], $notes['currency'], $notes['amount']);
 
                $data['reply_markup'] = Keyboard::remove(['selective' => true]);
                $data['text']      = $out_text;
               
                $this->conversation->stop();
                $result = Request::sendMessage($data);
                exit();
                break;
        }
        return $result;
    }
}