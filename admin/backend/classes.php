<?php

interface DataModuleInterface {
	function __construct($cookie);
	public function authUser($username, $password);
	public function isAuthed();
	public function authErrorCondition();
	public function getCookie();
	public function grabStoreConfig();
	public function grabExtraFlags();
	public function saveData($dataset);
}

class DataModule implements DataModuleInterface {
	private $store;
	private $cookie;
	private $authed;
	private $auth_error;
	
	function __construct($cookie) {
		$this->cookie = $cookie;
		$this->authed = false;
		$this->auth_error = 'No error';
		
		try {
			$this->store = new Bongo_Store('127.0.0.1', 689);
		} catch (Exception $e) {
			$this->auth_error = 'Could not connect to Bongo store';
			return null;
		}
	}
	
	public function authUser($user, $password) {
		if (! isset($this->store)) return;
		
		$res = $this->store->AuthUser($user, $password);
		if ($res->response_code == 1000) {
			$this->authed = true;
			$this->cookie = $this->store->CookieBake(60*60*24);
		} else {
			$this->auth_error = 'Username and/or password are incorrect';
		}
	}

	public function isAuthed() {
		if (! isset($this->store)) return false;
		
		if (($this->authed === false) && isset($this->cookie)) {
			$res = $this->store->AuthCookie('admin', $this->cookie);
			if ($res->response_code == 1000) {
				$this->authed = true;
			} else {
				$this->auth_error = 'Cookie is invalid.';
			}
		}
		return $this->authed;
	}

	public function authErrorCondition() {
		return $this->auth_error;
	}

	public function getCookie() {
		return $this->cookie;
	}

	public function collectDocumentsFromList($response, &$data) {
		if (($response->response_code == 2001) && ($response->type != Bongo::DOC_COLLECTION)) {
			array_push($data, array(
				'id' => $response->guid,
				'name' => $response->name
			));
		}
	}

	public function grabStoreConfig() {
		$result = array();
		try {
			$this->store->Store('_system');
			$callback = new Bongo_StoreCallback($this, 'collectDocumentsFromList', null);
			$callback->data = array();
			$this->store->CollectionList($callback, '/config');
			foreach ($callback->data as $config) {
				$file = $this->store->Read($config['id']);
				$namebits = explode('/', $config['name']);
				$name = $namebits[2];
				$result[$name] = json_decode($file->value, true);
			}
			
			// look for domain information
			$result['domains'] = array();
			$domain_maps = array();
			
			// FIXME: do I really need to co-erce this to a simple array? :(
			$result['queue']['domains'] = array_values($result['queue']['domains']);
			
			$domains = $result['queue']['domains'];
			array_unshift($domains, 'default_config');
			foreach ($domains as $domain) {
				$domain_config = $this->store->Read('/config/aliases/' . $domain);
				$config = json_decode($domain_config->value, true);
				
				if (isset($config['domainalias']) && ($config['domainalias'] != '')) {
					$domain_maps[$domain] = $config['domainalias'];
				}
				
				$config['name'] = $domain;
				$result['domains'][$domain] = $config;
				// change alias mapping to something more amenable.
				$list = $result['domains'][$domain]['aliases'];
				
				$newlist = array();
				foreach ($list as $from => $to) {
					array_push($newlist, array('from' => $from, 'to' => $to));
				}
				
				$result['domains'][$domain]['domainaliases'] = array();
				$result['domains'][$domain]['aliases'] = $newlist;
				$result['domains'][$domain]['accounts'] = array();
			}
			// merge the mapped domains information back in - do this afterwards
			// so we know we've already loaded the main config
			foreach ($domain_maps as $from => $to) {
				if (isset($result['domains'][$to])) {
					array_push($result['domains'][$to]['domainaliases'], $from);
				}
				// if there is no domain to stick it on, we have a config
				// problem that we ought to report really... FIXME
			}
			
			// look for user accounts
			$userCallback = new Bongo_StoreCallback(null, function($resp, &$data) {
				array_push($data, $resp->name);
			}, array());
			$userCallback->data = array();
			$this->store->Store();
			$this->store->AccountList($userCallback);
			foreach ($userCallback->data as $account) {
				$acpos = strpos($account, '@');
				if ($acpos === false) {
					array_push($result['domains']['default_config']['accounts'], array('name'=>$account));
				} else {
					$domain = substr($account, $acpos+1);
					array_push($result['domains'][$domain]['accounts'], array('name'=>$account));
				}
			}
		} catch (Exception $e) {
			return null;
		}
		return $result;
	}

	public function grabExtraFlags() {
		return array(
			'selected_domain' => 'default_config',
			'show_selected_domain' => false
		);
	}

	public function saveData($dataset) {
		$this->store->Store('_system');
		
		// save current data
		foreach ($dataset as $config => $configitems) {
			if ($config == 'aliases') continue;
			if (substr($config, 0, 1) == '_') continue;
			
			$filename = "/config/$config";
			$content = json_encode($configitems);
			if ($content != '')
				$this->store->Replace($filename, $content);
		}
		
		foreach ($dataset['aliases'] as $domain => $configitems) {
			$filename = "/config/aliases/$domain";
			
			// put the alias structure back the way it was
			$aliases = $configitems['aliases'];
			$configitems['aliases'] = array();
			foreach ($aliases as $alias) {
				$configitems['aliases'][$alias['from']] = $alias['to'];
			}
			
			$content = json_encode($configitems);
			
			if ($content != '') {
				$status = $this->store->Info($filename);
				if ($status->response_code == 2001) {
					$this->store->Replace($filename, $content);
				} else {
					$this->store->Write('/config/aliases', Bongo::DOC_CONFIG, $content, array('filename' => $domain));
				}
			}
		}
	}
}

class DataModuleDummy implements DataModuleInterface {
	function __construct($cookie) {
		// do nothing
	}
	
	public function authUser($user, $password) {
		return;
	}
	
	public function isAuthed() {
		return true;
	}
	
	public function getCookie() {
		return 'testcookie';
	}
	
	public function authErrorCondition() {
		return 'No error';
	}
	
	public function grabStoreConfig() {
		$root = getenv('BONGO_ROOT');
		$test_data = file_get_contents($root . '/test/server-config.json');
		return json_decode($test_data);
	}
	
	public function grabExtraFlags() {
		return array();
	}
	
	public function saveData($dataset) {
		return null;
	}
}
