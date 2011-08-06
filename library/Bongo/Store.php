<?php

class Bongo_StoreException extends Exception { }

class Bongo_StoreCallback 
{
	public $object;
	public $function;
	public $data;
	
	public function __construct($object, $function, $data) {
		$this->object = $object;
		$this->function = $function;
		$this->data = $data;
	}
}

class Bongo_Store extends Bongo_CommandStream
{
	private function doCallback(Bongo_StoreCallback $callback, $data) {
		$func = $callback->function;
		if (isset($callback->object)) {
			$obj = $callback->object;
			$obj->$func($data, $callback->data);
		} else {
			$func($data, $callback->data);
		}
	}
	
	private $logged_user = '';
	public function AuthUser($user, $password) {
		$this->SendCommand("AUTH USER $user $password");
		$this->logged_user = $user;
		return $this->GetLineResponse();
	}
	
	public function AuthCookie($user, $cookie) {
		$this->SendCommand("AUTH COOKIE $user $cookie");
		$this->logged_user = $user;
		return $this->GetLineResponse();
	}
	
	public function AuthSystem($password) {
		// TODO
		return NULL;
	}
	
	public function Collections(Bongo_StoreCallback $callback, $collection = '') {
		$this->SendCommand("COLLECTIONS $collection");
		
		/* We loop "forever" and call the callback function for every line
		 * which contains information (codes 2001). Any other response code,
		 * we jump out of the loop and return the response - it should be the
		 * last line.
		 */
		while(TRUE) {
			$response = $this->GetLineResponse('Bongo_CommandStreamResponse_Collections');
			if ($response->response_code == 2001) {
				$this->doCallback($callback, $response);
			} else {
				return $response;
			}
		}
	}
	
	public function CookieBake($timeout = '') {
		$this->SendCommand("COOKIE BAKE $timeout");
		$response = $this->GetLineResponse('Bongo_CommandStreamResponse_CookieBake');
		if ($response->response_code == 1000) {
			return $response->cookie;
		} else {
			// throw an error?
			return NULL;
		}
	}
	
	public function CookieCrumble($cookie) {
		$this->SendCommand("COOKIE CRUMBLE $cookie");
		return $this->GetLineResponse();
	}
	
	public function Copy($document, $collection) {
		$this->SendCommand("COPY $document $collection");
		return $this->GetLineResponse();
	}
	
	public function Create($collection, $guid = '') {
		$this->SendCommand("CREATE $collection $guid");
		return $this->GetLineResponse('Bongo_CommandStreamResponse_Create');
	}
	
	// can't call this List because it's a PHP method name :(
	public function CollectionList($callback, $collection, Bongo_Store_Query $query = null) {
		$qstr = isset($query)? $query->getQueryArgument() : '';
		$this->SendCommand("LIST $collection $qstr");
		
		while(TRUE) {
			$response = $this->GetLineResponse('Bongo_CommandStreamResponse_Info');
			if ($response->response_code == 2001) {
				$this->doCallback($callback, $response);
			} else {
				return $response;
			}
		}
	}
	
	public function Delete($document) {
		$this->SendCommand("DELETE $document");
		return $this->GetLineResponse();
	}
	
	public function Flag($document, $value, $modify=FALSE) {
		$sign = '';
		$number = abs($value);
		if ($modify) $sign = ($value > 0)? '+' : '-';
		
		$this->SendCommand("FLAG $document $sign$number");
		return $this->GetLineResponse();
	}
	
	public function Info($document) {
		$this->SendCommand("INFO $document");
		
		$response = $this->GetLineResponse('Bongo_CommandStreamResponse_Info');
		if ($response->response_code == 2001) {
			// eat up next response line.. yuck
			$done_response = $this->GetLineResponse();
		}
		return $response;
	}
	
	public function Link($document, $related) {
		$this->SendCommand("LINK $document $related");
		return $this->GetLineResponse();
	}
	
	public function Mime($document, Bongo_StoreCallback $callback) {
		$this->SendCommand("MIME $document");
		
		while(TRUE) {
			$response = $this->GetLineResponse('Bongo_CommandStreamResponse_Mime');
			if (($response->response_code >= 2002) && ($response->response_code <= 2004)) {
				$this->doCallback($callback, $response);
			} else {
				return $response;
			}
		}
	}
	
	public function Move($document, $collection, $new_name = '') {
		$this->SendCommand("MOVE $document $collection $new_name");
		return $this->GetLineResponse();
	}
	
	public function PropGet($document, Bongo_StoreCallback $callback, $property_list = array()) {
		$propset = '';
		if (count($property_list) > 0)
			$propset = join(',', $property_list);
		
		$this->SendCommand("PROPGET $document $propset");
		
		// this ugliness because PROPSET doesn't always give a 1000 return code?
		if (count($property_list) > 0) {
			for ($i = 0; $i < count($property_list); $i++) {
				$response = $this->GetLineResponse('Bongo_CommandStreamResponse_PropGet');
				$this->doCallback($callback, $response);
			}
		} else {
			while (TRUE) {
				$response = $this->GetLineResponse('Bongo_CommandStreamResponse_PropGet');
				if ($response->response_code == 1000) 
					return;
				
				$this->doCallback($callback, $response);
			}
		}
	}
	
	public function PropSet($document, $property, $value) {
		$len = strlen($value);
		$this->SendCommand("PROPSET $document $property $len");
		$response = $this->GetLineResponse();
		if ($response->response_code == 2002) {
			// TODO : could do more to check for errors here
			$this->Send($value);
			$response = $this->GetLineResponse();
			if ($response->response_code != 1000) 
				throw new Bongo_StoreException("Can't set property");
		} else {
			throw new Bongo_StoreException("Can't set property");
		}
	}
	
	public function Read($document, $start='', $len='') {
		$this->SendCommand("READ $document $start $len");
		return $this->GetLineResponse('Bongo_CommandStreamResponse_PropGet');
	}
	
	public function Remove($collection) {
		$this->SendCommand("REMOVE $collection");
		return $this->GetLineResponse();
	}
	
	public function Rename($old_name, $new_name) {
		$this->SendCommand("RENAME $old_name $new_name");
		return $this->GetLineResponse();
	}
	
	public function Replace($document, $content) {
		$len = strlen($content);
		$this->SendCommand("REPLACE $document $len");
		$response = $this->GetLineResponse();
		if ($response->response_code == 2002) {
			$this->Send($content);
			$response = $this->GetLineResponse();
			if ($response->response_code != 1000) 
				throw new Bongo_StoreException("Can't replace content");
		} else {
			throw new Bongo_StoreException("Can't replace content");
		}
	}
	
	public function Stores(Bongo_StoreCallback $callback) {
		$this->SendCommand("STORES");
		
		/* We loop "forever" and call the callback function for every line
		 * which contains information (codes 2001). Any other response code,
		 * we jump out of the loop and return the response - it should be the
		 * last line.
		 */
		while(TRUE) {
			$response = $this->GetLineResponse('Bongo_CommandStreamResponse_Stores');
			if ($response->response_code == 2001) {
				$this->doCallback($callback, $response);
			} else {
				return $response;
			}
		}
	}
	
	public function Store($store = '') {
		$this->SendCommand("STORE $store");
		return $this->GetLineResponse();
	}
	
	public function UserOwnStore() {
		if ($this->logged_user != '') {
			return $this->Store($this->logged_user);
		}
	}
	
	public function Quit() {
		try {
			$this->SendCommand("QUIT");
		} catch (Exception $e) { }
		
		$this->Disconnect();
	}
	
	public function Unlink($document, $related) {
		$this->SendCommand("UNLINK $document $related");
		return $this->GetLineResponse();
	}
	
	public function Write($collection, $type, $content, $options) {
		$len = strlen($content);
		if ($len <= 0) {
			throw new Bongo_StoreException("Can't write empty document");
		}
		
		$opts = array();
		if (isset($options['filename'])) array_push($opts, 'F' . $options['filename']);
		if (isset($options['guid'])) array_push($opts, 'G' . $options['guid']);
		if (isset($options['ctime'])) array_push($opts, 'C' . $options['ctime']);
		if (isset($options['flags'])) array_push($opts, 'Z' . $options['flags']);
		$opts_string = implode(' ', $opts);
		
		$this->SendCommand("WRITE $collection $type $len $opts_string");
		$response = $this->GetLineResponse();
		if ($response->response_code == 2002) {
			$this->Send($content);
			$last_resp = $this->GetLineResponse('Bongo_CommandStreamResponse_Write');
			if ($last_resp->response_code != 1000) throw new Bongo_StoreException("Can't write document");
			return $last_resp->guid;
		} else {
			throw new Bongo_StoreException("Can't write document");
		}
	}
}
