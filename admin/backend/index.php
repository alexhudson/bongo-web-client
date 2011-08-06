<?php

/* Backend administrative server
 */

error_reporting(E_ALL | E_STRICT | E_NOTICE);
ini_set('display_errors', 'On');
ini_set('display_startup_errors', true);

$root = getenv('BONGO_ROOT');
define("BONGO_AUTO_LOAD", $root . '/library');
require_once($root . 'library/Bongo.php');
require_once('classes.php');

$command = $_POST['command'];

$cookie = null;
if (isset($_GET['cookie'])) $cookie = $_GET['cookie'];
if (isset($_POST['cookie'])) $cookie = $_POST['cookie'];

$mode = getenv('APPLICATION_ENV');
if ($mode == 'testing') {
	$server = new DataModuleDummy($cookie);
} else {
	$server = new DataModule($cookie);
}

$result = array('status' => 'fail', 'message' => 'Unknown error');

switch ($command) {
	case 'login':
		if (isset($_POST['name']) && isset($_POST['password']))
			$server->authUser($_POST['name'], $_POST['password']);
		
		if ($server->isAuthed()) {
			$result['status'] = 'ok';
			$result['message'] = 'Login OK';
			$result['cookie'] = $server->getCookie();
			$result['data'] = $server->grabStoreConfig();
			$result['uiflag'] = $server->grabExtraFlags();
		} else {
			$result['message'] = $server->authErrorCondition();
		}
		// TODO : Need to detect "cannot connect" error condition
		break;
	case 'savedata':
		if ($server->isAuthed()) {
			try {
				$dataset = json_decode($_POST['data'], true);
				$message = $server->saveData($dataset);
			} catch (Exception $e) {
				$message = "Couldn't decode data";
			}
			if (isset($message)) {
				$result['message'] = $message;
			} else {
				$result['status'] = 'ok';
			}
		} else {
			$result['message'] = 'Cookie timed out';
		}
		break;
	default:
		$result['message'] = 'Unknown command used: ' . $command;
		break;
}

if ($result['status'] == 'fail') {
	header('HTTP/1.1 555 JSON server failed');
}
header("Content-type: text/json", true);

echo json_encode($result);
