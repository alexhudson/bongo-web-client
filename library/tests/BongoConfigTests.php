<?php

define('BONGO_AUTO_LOAD', TRUE);
require_once('Bongo.php');

class BongoConfigTests extends PHPUnit_Framework_TestCase
{
	// this test needs a working accessible local Bongo store
	public function testConfigured() {
		$stores = Bongo::getStoreConfiguration();
		
		$this->assertTrue(is_array($stores), "Non-array response to configuration request");
		$this->assertTrue(count($stores) > 0, "No stores configured or couldn't read");
	}
}
