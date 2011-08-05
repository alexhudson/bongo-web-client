<?php

class DataModule {
	private $store;
	
	function getStoreHandle() {
		if (isset($this->store)) return $this->store;
		
		try {
			$this->store = new Bongo_Store('127.0.0.1', 689);
		} catch (Exception $e) {
			return null;
		}
		return $this->store;
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
				$result[$config['name']] = json_decode($file->value, true);
			}
		} catch (Exception $e) {
			return null;
		}
		return $result;
	}
}
