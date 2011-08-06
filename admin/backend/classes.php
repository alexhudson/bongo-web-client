<?php

interface DataModuleInterface {
	function __construct($cookie);
	function isAuthed();
	function getCookie();
	function grabStoreConfig();
	function grabExtraFlags();
}

class DataModule implements DataModuleInterface {
	private $store;
	private $cookie;
	private $authed;
	
	function __construct($cookie) {
		$this->cookie = $cookie;
		$this->authed = false;
	}
	
	protected function getStoreHandle($user, $password) {
		if (isset($this->store)) return $this->store;
		
		try {
			$this->store = new Bongo_Store('127.0.0.1', 689);
		} catch (Exception $e) {
			return null;
		}
		
		if (isset($this->cookie)) {
			$res = $this->store->AuthCookie('admin', $this->cookie);
			if ($res->response_code == 1000) {
				$this->authed = true;
			}
		} elseif (isset($password)) {
			$res = $this->store->AuthUser($user, $password);
			if ($res->response_code == 1000) {
				$this->authed = true;
				$this->cookie = $this->store->CookieBake(60*60*24);
			}
		}
		
		return $this->store;
	}

	function isAuthed() {
		return $this->authed;
	}

	function getCookie() {
		return $this->cookie;
	}

	function collectDocumentsFromList($response, &$callback) {
		if (($response->response_code == 2001) && ($response->type != Bongo::DOC_COLLECTION)) {
			array_push($callback->data, array(
				'id' => $response->guid,
				'name' => $response->name
			));
		}
	}

	function grabStoreConfig() {
		$result = array();
		try {
			$this->store->Store('_system');
			$callback = new Bongo_StoreCallback($this, 'collectDocumentsFromList');
			$callback->data = array();
			$this->store->CollectionList($callback, '/config');
			foreach ($callback->data as $config) {
				$file = $this->store->Read($config['id']);
				$namebits = explode('/', $config['name']);
				$name = $namebits[2];
				$result[$name] = json_decode($file->value, true);
			}
		} catch (Exception $e) {
			return null;
		}
		return $result;
	}

	function grabExtraFlags() {
		return array();
	}
}

class DataModuleDummy implements DataModuleInterface {
	function __construct($cookie) {
		// do nothing
	}
	
	function isAuthed() {
		return true;
	}
	
	function getCookie() {
		return 'testcookie';
	}
	
	function grabStoreConfig() {
		$root = getenv('BONGO_ROOT');
		$test_data = file_get_contents($root . '/test/server-config.json');
		return json_decode($test_data);
	}
	
	function grabExtraFlags() {
		return array();
	}
}
