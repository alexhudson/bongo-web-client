<?php

class Bongo_CommandStreamNetworkException extends Exception {}
class Bongo_CommandStreamResponseException extends Exception {}

class Bongo_CommandStream 
{
	private $connection;
	private $auth_needed = TRUE;
	
	public function __construct($server = NULL, $port = NULL) {
		if (isset($server) && isset($port)) {
			$this->Connect($server, $port);
		}
	}
	
	public function __destruct() {
		$this->Disconnect();
	}
	
	public function Connect($server, $port) {
		$this->connection = @fsockopen($server, $port);
		if (! $this->connection) {
			throw new Bongo_CommandStreamNetworkException('Could not connect to server');
		}
		$response = $this->GetLineResponse();
		if ($response->response_code == 1000) {
			$this->auth_needed = FALSE;
		} else {
			// TODO - save banner because AUTH SYSTEM needs it
		}
	}
	
	public function Disconnect() {
		if ($this->connection) {
			fclose($this->connection);
			$this->connection = NULL;
		}
	}
	
	public function CheckConnected() {
		if (! $this->connection) {
			throw new Bongo_CommandStreamNetworkException('Not connected');
		}
	}
	
	public function SendCommand($line) {
		$this->CheckConnected();
		
		if (fwrite($this->connection, $line . "\r\n") === FALSE) {
			$this->Disconnect();
			throw new Bongo_CommandStreamNetworkException('Could not write data to stream');
		}
	}
	
	public function Send($data) {
		fwrite($this->connection, $data);
	}
	
	/* Read the response from the Bongo server, and turn it into a CommandStreamResponse.
	 * Where extended information is returned - e.g., CREATE, COLLECTIONS, we allow an
	 * extended version of CommandStreamResponse to be used so that we can parse this 
	 * extra information
	 * FIXME: at some point, we want to do this in a streaming fashion.
	 */
	public function GetLineResponse($type = 'Bongo_CommandStreamResponse') {
		$response = new $type($this->GetLine());
		
		if ($response instanceOf Bongo_CommandStreamResponseWithPayload) {
			if ($response->size > 0) {
				$size = $response->size;
				$data = '';
				while ($size > 0) {
					$newdata = fread($this->connection, ($size > 8192)? 8192 : $size);
					$size -= strlen($newdata);
					$data .= $newdata;
				}
				fread($this->connection, 2); // eat the \r\n
				$response->SetValue($data);
			}
		}
		return $response;
	}
	
	/* This simply reads the response back from the server
	 */
	public function GetLine() {
		$this->CheckConnected();
		
		$line = fgets($this->connection);
		if ($line === FALSE) {
			$this->Disconnect();
			throw new Bongo_CommandStreamNetworkException('Could not read line from stream');
		}
		return $line;
	}
}
