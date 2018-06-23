<?php
define('TWIKEY_DEBUG',true);
require_once( 'Twikey.php' );

$twikey = new Twikey();
$twikey->setEndpoint('https://api.twikey.com');
$twikey->setApiToken('<<myToken>>');
