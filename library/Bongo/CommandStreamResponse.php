<?php

/* Generic command response class - used to read responses from the Bongo server.
 * This basic class just looks at response codes - e.g. "1000 OK", "4000 Error".
 * Classes derived from this cope with more complexity, e.g. "2001 <guid> <name>"
 */

class Bongo_CommandStreamResponse
{
	public $response_code;
	
	public function __construct($line) {
		if (strlen($line) < 5) {
			throw new Bongo_CommandStreamResponseException("Invalid response '$line'");
		}
		$this->response_code = max(0, (int)substr($line, 0, 4));
		
		$rest_of_line = rtrim(substr($line, 5));
		$this->ProcessResponse($rest_of_line);
	}
	
	public function ProcessResponse($data) {
		// do nothing
	}
}

class Bongo_CommandStreamResponse_Create extends Bongo_CommandStreamResponse
{
	public $guid;
	public $ctime;
	
	public function ProcessResponse($data) {
		if ($this->response_code == 1000) {
			$args = explode(" ", $data);
			$this->guid = $args[0];
			$this->ctime = $args[1];
		}
	}
}

class Bongo_CommandStreamResponse_Write extends Bongo_CommandStreamResponse
{
	public $guid;
	
	public function ProcessResponse($data) {
		if ($this->response_code == 1000) {
			$args = explode(" ", $data);
			$this->guid = $args[0];
		}
	}
}

// 2001 <store>
class Bongo_CommandStreamResponse_Stores extends Bongo_CommandStreamResponse
{
	public $name;
	
	public function ProcessResponse($data) {
		if ($this->response_code == 2001) {
			$bits = explode(" ", $data);
			
			$this->name = $bits[0];
		}
	}
}

class Bongo_CommandStreamResponse_Collections extends Bongo_CommandStreamResponse 
{
	public $guid;
	public $type;
	public $flags;
	public $name;
	
	public function ProcessResponse($data) {
		// not sure expode is the best way - doesn't really validate the data...
		// FIXME - check we handle spaces in names.
		if ($this->response_code == 2001) {
			$args = explode(" ", $data);
			$this->guid = $args[0];
			$this->type = $args[1];
			$this->flags = $args[2];
			$this->name = $args[3];
		}
	}
}

class Bongo_CommandStreamResponse_CookieBake extends Bongo_CommandStreamResponse
{
	public $cookie;
	
	public function ProcessResponse($data) {
		$this->cookie = $data;
	}
}

// 2002 <guid> <type> <flags> <uid> <store time> <document size> <filename> 
class Bongo_CommandStreamResponse_Info extends Bongo_CommandStreamResponse
{
	public $guid;
	public $type;
	public $flags;
	public $imap_uid;
	public $mtime;
	public $size;
	public $name;
	
	public function ProcessResponse($data) {
		if ($this->response_code != 2001) return;
		
		$bits = explode(" ", $data);
		
		$this->guid = array_shift($bits);
		$this->type = array_shift($bits);
		$this->flags = array_shift($bits);
		$this->imap_uid = array_shift($bits);
		$this->mtime = array_shift($bits);
		$this->size = array_shift($bits);
		$this->name = join(" ", $bits);
	}
}

// 2002 <type> <subtype> <charset> <encoding> <name> <header start> <header len> <part start> <part size> <header size> <total # of lines> 
class Bongo_CommandStreamResponse_Mime extends Bongo_CommandStreamResponse
{
	public $type;
	public $subtype;
	public $charset;
	public $encoding;
	public $name;
	public $header_start;
	public $header_len;
	public $part_start;
	public $part_size;
	public $header_size;
	public $lines;
	
	public function ProcessResponse($data) {
		// TODO
	}
}

// some commands give a binary octet length and then a payload of data, of that length
// this base class is recognised by CommandStream, and if the response is of this type
// it attempts to read that binary data and load it in.
class Bongo_CommandStreamResponseWithPayload extends Bongo_CommandStreamResponse {
	public $size;
	public $value;
	
	public function SetValue($data) {
		$this->value = $data;
	}
}

// 2001 <property> <size>
class Bongo_CommandStreamResponse_PropGet extends Bongo_CommandStreamResponseWithPayload
{
	public $property;
	public $size;
	
	public function ProcessResponse($data) {
		if ($this->response_code == 2001) {
			$bits = explode(" ", $data);
			
			$this->property = $bits[0];
			$this->size = $bits[1];
		}
	}
}

