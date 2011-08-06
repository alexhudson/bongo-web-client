<?php

define('BONGO_AUTO_LOAD', TRUE);
require_once('Bongo.php');

class StoreOnlineTests extends PHPUnit_Framework_TestCase
{
	// this test needs a working accessible local Bongo store
	public function testBasicReadAndWrite() {
		$sc = new Bongo_Store('localhost', 689);
		$sc->AuthUser('admin', 'bongo');
		$sc->Store('admin');
		
		$testcollection = '/testcol-abrw';
		$sc->Remove($testcollection);
		$r = $sc->Create($testcollection);
		$this->assertTrue(($r->response_code == 1000), "Couldn't create $testcollection");
		
		$contact1 = new Bongo_Document_Contact();
		try {
			$contact1->load($sc, $testcollection . '/testcontact1');
			$this->fail('Read back contact that should not exist');
		} catch (Exception $e) {
			$this->assertTrue(TRUE, "Didn't read nonexistant contact");
		}
		
		$name = 'Test contact';
		$email = 'test@example.com';
		
		$contact1->setFullname($name);
		$this->assertTrue($contact1->getFullname() == $name,
			"Couldn't set fullname on contact");
		
		$contact1->addEmail($email);
		$this->assertTrue($contact1->getPreferredEmail() == $email,
			"Couldn't set email on contact");
		
		try {
			$id = $contact1->saveNew($sc, $testcollection);
		} catch (Exception $e) {
			$this->fail("Couldn't write back contact1: $e");
		}
		
		$contact2 = new Bongo_Document_Contact();
		try {
			$contact2->load($sc, $id);
		} catch (Exception $e) {
			$this->fail("Couldn't load contact2: $e");
		}
		
		$this->assertTrue($contact1->getFullname() == $contact2->getFullname(),
			"Fullname corrupted on reloaded contact");
		$this->assertTrue($contact1->getPreferredEmail() == $contact2->getPreferredEmail(),
			"Email corrupted on reloaded contact");
		
		$sc->Remove($testcollection);
	}
}

