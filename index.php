<?php
define("SIMPLE_FAUCET",true);
require_once('./lib/jsonRPCClient.php');
require_once('./lib/simple_faucet.php');
require_once('./lib/faucet_rpc.php');
require_once('./lib/proxy_filter/proxy_filter.php');
require_once('./config.php');
if (!isset($config) || !class_exists("jsonRPCClient") || !class_exists("simple_faucet"))
	die("Faucet missing core components and/or config.");
$simple_faucet = new simple_faucet($config);
$simple_faucet_rpc = new simple_faucet_rpc($config);
if ($simple_faucet_rpc->process_request()) {
	die();
} else {
	$simple_faucet->render();
}
