<?php
/** 
 * Webhook file. User can access via /messenger/
 */

if ( isset( $_REQUEST['hub_verify_token'] ) && $_REQUEST['hub_verify_token'] == 'FacebookMessengerBots' ) 
{
	echo $_REQUEST['hub_challenge'];

	exit;
}

require_once 'loader.php';

require_once 'example/example.php';
