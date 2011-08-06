<?php

/**
 * This is extremely basic and not a very good API.
 * Ideally, we'd want something fluent, like:
 * $q = new Bongo_Store_Query();
 * $q->and($q->PropIs('nmap.type', 4),
 *         $q->PropIs('bongo.from', 'test@example.com'));
 */

class Bongo_Store_Query
{
	private $query;
	
	public function __construct($query = '') {
		$this->query = $query;
	}
	
	public function getQueryArgument() {
		if ($this->query == '') return '';
		
		$query = str_replace('"', '\\"', $this->query);
		
		return "\"Q$query\"";
	}
}
