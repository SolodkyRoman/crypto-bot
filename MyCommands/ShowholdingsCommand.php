<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\TradeDB;
use Longman\TelegramBot\ApiRequests;

class ShowholdingsCommand extends UserCommand
{	
    protected $name = 'showholdings';                      // Your command's name
    protected $description = 'A command for test'; // Your command description
    protected $usage = '/showholdings';                    // Usage of your command
    protected $version = '1.0.0';                  // Version of your command

    public function execute()
    {
        $message = $this->getMessage();            // Get Message object
        $chat_id = $message->getChat()->getId();   // Get the current Chat ID

        $data = [                                  // Set up the new message data
            'chat_id' => $chat_id,                 // Set Chat ID to send the message to
            'text' => ''
        ];

        /** select all user's holdings from the database */
            $holdings = TradeDB::selectUsersHoldings($chat_id);

        /** send info message if there aren't any user's holdings */
            if (empty($holdings)) {
                $data['text'] = 'You don\'t have any holdings yet. You can add coins by /editcoin command.';
                $result = Request::sendMessage($data);        // Send message!
                exit();
                return $result;
            }

        /** get holdings' prices and price changes */
            $out_text = ApiRequests::getPrices($holdings, 'USDT');

        /** set first exchange's name */
        //     $out_text = 'Exchange: ' . ucfirst($holdings[0]['exchange_name']) . PHP_EOL . PHP_EOL;

        // for($i = 0; $i < count($holdings); $i++) {
        //     if ($i != 0) {
        //         if ($holdings[$i]['exchange_name'] != $holdings[$i - 1]['exchange_name']) {
        //             $out_text .= 'Exchange: ' . $holdings[$i]['exchange_name'] . PHP_EOL . PHP_EOL;
        //         }
        //     }
        //     $out_text .= $holdings[$i]['currency_name'] . ': ' . $holdings[$i]['currency_amount'] . PHP_EOL;
        // }

        $data['text'] = $out_text;
        $result = Request::sendMessage($data);        // Send message!
        exit();
        return $result;
    }
}