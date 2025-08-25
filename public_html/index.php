<?php

// ini_set('display_errors', '1');
// ini_set('display_startup_errors', '1');
// error_reporting(E_ALL);

require dirname(__DIR__) . '/src/config.php';
require dirname(__DIR__) . '/src/router.php';

// Views
route('GET', '/', 'WebAppController@index');

// Order creation
route('POST', '/create-order', 'OrderController@create');

// Telegram webhook
route('POST', '/webhook', 'BotController@webhook');

// (optional) direct send
route('POST', '/send', 'BotController@send');

dispatch($_SERVER['REQUEST_METHOD'], parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
