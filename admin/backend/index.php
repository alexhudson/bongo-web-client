<?php

/* Backend administrative server
 */

$root = getenv('BONGO_ROOT');

error_reporting(E_ALL & E_STRICT);
define("BONGO_AUTO_LOAD", $root . '/library');
require_once($root . 'library/Bongo.php');
require_once('classes.php');

$command = $_POST['command'];

$cookie = null;
if (isset($_GET['cookie'])) $cookie = $_GET['cookie'];
if (isset($_POST['cookie'])) $cookie = $_POST['cookie'];
$server = new DataModule($cookie);

$result = array('status' => 'fail', 'message' => 'Unknown error');

switch ($command) {
	case 'login':
		$store = $server->getStoreHandle($_POST['name'], $_POST['password']);
		if ($store !== null) {
			if ($server->isAuthed()) {
				$result['status'] = 'ok';
				$result['message'] = 'Login OK';
				$result['cookie'] = $server->getCookie();
				$result['data'] = $server->grabStoreConfig($store);
				$result['uiflag'] = $server->grabExtraFlags();
			} else {
				$result['message'] = 'Username and/or password are incorrect';
			}
		} else {
			$result['message'] = 'Could not connect to Bongo Store';
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
