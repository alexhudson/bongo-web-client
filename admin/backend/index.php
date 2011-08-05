<?php

/* Backend administrative server
 */

$root = getenv('BONGO_ROOT');

error_reporting(E_ALL & E_STRICT);
define("BONGO_AUTO_LOAD", $root . '/library');
require_once($root . 'library/Bongo.php');

$command = $_POST['command'];

function getStoreHandle() {
	try {
		$store = new Bongo_Store('127.0.0.1', 689);
	} catch (Exception $e) {
		return null;
	}
	return $store;
}

$result = array('status' => 'fail', 'message' => 'Unknown error');

switch ($command) {
	case 'login':
		$store = getStoreHandle();
		if ($store !== null) {
			$res = $store->AuthUser($_POST['name'], $_POST['password']);
			if ($res->response_code == 1000) {
				$result['status'] = 'ok';
				$result['message'] = 'Login OK';
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
