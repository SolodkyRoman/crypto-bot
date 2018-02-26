#!/usr/bin/env php
<?php
require __DIR__ . '/vendor/autoload.php';
include __DIR__ . '/TradeDB.php';
include __DIR__ . '/ApiRequests.php';

$bot_api_key  = '';
$bot_username = '';

$commands_paths = [ 
  __DIR__ . '/MyCommands/',
];

$mysql_credentials = [
   'host'     => 'localhost',
   'user'     => 'root',
   'password' => 'root',
   'database' => 'crypto-bot',
];

try {
    // Create Telegram API object
    $telegram = new Longman\TelegramBot\Telegram($bot_api_key, $bot_username);

    // Add commands paths containing your custom commands
    $telegram->addCommandsPaths($commands_paths);

    // Enable MySQL
    $telegram->enableMySql($mysql_credentials);

    // Logging (Error, Debug and Raw Updates)
    // Longman\TelegramBot\TelegramLog::initErrorLog(__DIR__ . "/{$bot_username}_error.log");
    // Longman\TelegramBot\TelegramLog::initDebugLog(__DIR__ . "/{$bot_username}_debug.log");
    // Longman\TelegramBot\TelegramLog::initUpdateLog(__DIR__ . "/{$bot_username}_update.log");
  while(true)
  {

    sleep(1);
      // Handle telegram getUpdates request
      $response = $telegram->handleGetUpdates();
  }
} catch (Longman\TelegramBot\Exception\TelegramException $e) {
    // log telegram errors
    echo $e->getMessage();
}