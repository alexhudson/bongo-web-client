<?php

class Bongo_Document_Contact 
{
	private $document;
	private $id;
	private $data;
	
	// constructor creates a basic minimal jCard. Useful for
	// creating new documents, gets overwritten if we're loading.
	public function __construct($client = null, $id = null) {
		if (($client != null) && ($id != null)) {
			$this->load($client, $id);
			return;
		}
		$this->data = array(
			'jCard' => 3.0,
			'fullName' => '',
			'name' => array(
				'familyName' => array(),
				'givenName' => array(),
				'additionalNames' => array(),
				'prefix' => array(),
				'suffix' => array(),
			)
		);
	}
	
	// Basic utility functions; not trying to cover the whole
	// data structure here.
	public function setFullname($name) {
		$this->data['fullName'] = $name;
	}
	public function getFullname() {
		return $this->data['fullName'];
	}
	
	public function addEmail($address) {
		// check that we don't already have it
		if (isset($this->data['email'])) {
			foreach ($this->data['email'] as $details) {
				if ($details['address'] == $address)
					// we already have this email
					return FALSE;
			}
		} else {
			$this->data['email'] = array();
		}
		// set up new address
		$detail = array(
			'address' => $address,
			'type' => array('internet')
		);
		array_push($this->data['email'], $detail);
		return TRUE;
	}
	public function removeEmail($address) {
		if (! isset($this->data['email'])) return FALSE;
		$result = 0;
		
		foreach($this->data['email'] as $i => $detail) {
			if ($detail['address'] == $address) {
				unset($this->data['email'][$i]);
				$result++;
			}
		}
		if ($result)
			$this->data['email'] = array_values($this->data['email']);
		
		return $result;
	}
	public function removeAllEmail() {
		unset($this->data['email']);
		return TRUE;
	}
	// return the first preferred address, else the first address, else null
	public function getPreferredEmail() {
		if (! isset($this->data['email'])) return null;
		$result = 0;
		
		foreach($this->data['email'] as $i => $detail) {
			if (in_array('preferred', $detail['type'])) {
				return $detail['address'];
			}
		}
		return $this->data['email'][0]['address'];
	}
	// set the preferred address. If the address is not found, there is
	// no preference will remain.
	public function setPreferredEmail($address) {
		if (! isset($this->data['email'])) return FALSE;
		$result = FALSE;
		
		foreach($this->data['email'] as $i => $detail) {
			if ($detail['address'] == $address) {
				if (!in_array($detail['type'], 'preferred')) {
					array_push($detail['type'], 'preferred');
				}
			} else {
				if (in_array($detail['type'], 'preferred')) {
					foreach($detail['type'] as $i => $type) {
						if ($type == 'preferred') unset($detail['type'][$i]);
					}
					$detail['type'] = array_values($detail['type']);
				}
			}
		}
	}
	
	/* getData / setData used to access raw jCard data.
	 * Apps doing this need to know what they're doing.
	 */
	public function getData() {
		return $this->data;
	}
	
	public function setData($data) {
		$this->data = $data;
	}
	
	/* Utility functions to easily load/save from the Store. Don't 
	 * have to go this route, but it does basic checking etc. for you.
	 */
	public function load(Bongo_Store $client, $document) {
		$content = $client->Read($document);
		$contact = json_decode($content->value, TRUE);
		if (!isset($contact['jCard']) || ((int)$contact['jCard'] != 3)) {
			throw new Exception("Content not jCard 3.0");
		}
		$this->data = $contact;
		$this->document = $document;
		
		// we fetch the info at this point because we use the store
		// guid as external guid for roundcube.
		$info = $client->Info($document);
		$this->id = $info->guid;
	}
	
	public function save(Bongo_Store $client) {
		$client->Replace($this->id, json_encode($this->data));
		// Shouldn't be handling this here, should be in the Store.
		$client->Propset($this->id, 'bongo.contact.name', $this->data['fullName']);
		$client->Propset($this->id, 'bongo.contact.email', $this->getPreferredEmail());
		return $this->id;
	}
	
	public function saveNew(Bongo_Store $client, $collection, $filename = null) {
		$options = array();
		if ($filename != null) $options['filename'] = $filename;
		
		$id = $client->Write($collection, 4, json_encode($this->data), $options);
		// Shouldn't be handling this here, should be in the Store.
		$client->Propset($id, 'bongo.contact.name', $this->data['fullName']);
		$client->Propset($id, 'bongo.contact.email', $this->getPreferredEmail());
		
		$this->document = $id;
		$this->id = $id;
		
		return $id;
	}
	
	/* Roundcube utility functions: transforms jCard into the internal format used.
	 * Data should look like: 
	 * array ( 'ID' => '', 'name' => '', 'firstname' => '', 'surname' => '', 'email' => '')
	 */
	public function toRoundcube() {
		// always select first address, because it only knows about one.
		$email = '';
		if (count($this->data['email']) > 0)
			$email = $this->data['email'][0]['address'];
		
		$rccontact = array(
			'ID' => $this->document,
			'name' => $this->data['fullName'],
			'firstname' => $this->data['name']['givenName'][0],
			'surname' => $this->data['name']['familyName'][0],
			'email' => $email
		);
		return $rccontact;
	}
	
	public function fromRoundcube($data) {
		$this->data['fullName'] = $data['name'];
		$this->data['name']['givenName'] = array($data['firstname']);
		$this->data['name']['familyName'] = array($data['surname']);
		if (isset($this->data['email']) && (count($this->data['email']) > 0)) {
			$this->data['email'][0]['address'] = $data['email'];
		} else {
			$this->data['email'] = array (
				array(
				'address' => $data['email'],
				'type' => array ('internet')
				)
			);
		}
	}
}
