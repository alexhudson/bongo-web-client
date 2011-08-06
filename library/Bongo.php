<?php

class Bongo {
	static private $config;
	
	/**
	 * Attempt to load configuration object from system config file.
	 * 
	 * @return bool Whether or not it succeeded.
	 */
	static private function loadConfiguration() {
		if (isset(Bongo::$config)) return TRUE;
		
		$content = @file_get_contents("/etc/bongo.conf");
		if ($content === FALSE) return FALSE;
		
		$config = @json_decode($content, TRUE);
		if (!isset($content)) return FALSE;
		
		if ($config['type'] != 'bongo-config') return FALSE;
		
		Bongo::$config = $config;
		return TRUE;
	}
	
	/**
	 * Get list of Bongo stores that are 'known good' according to local configuration.
	 * 
	 * Each potential store is an array of items, currently:
	 * - 'host', the name / IP address of the server to 
	 * - 'port', the post on which to connect (default: 689)
	 * 
	 * It may have additional information, do not assume the keys used 
	 * or the order in which they may appear.
	 * 
	 * If no configuration is found, an empty array is returned. Without configuration,
	 * you could try not passing parameters to Bongo_Store(), or asking the user.
	 * 
	 * @return Array of potential stores
	 */
	public static function getStoreConfiguration() {
		if (Bongo::loadConfiguration()) {
			if (is_array(Bongo::$config['stores'])) {
				return Bongo::$config['stores'];
			}
		}
		return array();
	}
	
	/* Various types and fixed values */
	const DOC_UNTYPED = 1;
	const DOC_MAIL = 2;
	const DOC_EVENT = 3;
	const DOC_CONTACT = 4;
	const DOC_CONVERSATION = 5;
	const DOC_CONFIG = 7;

	const DOC_COLLECTION = 4096; // poor name really.
	
	const FLAG_PURGED = 1;
	const FLAG_SEEN = 2;
	const FLAG_ANSWERED = 4;
	const FLAG_FLAGGED = 8;
	const FLAG_DELETED = 16;
	const FLAG_DRAFT = 32;
	const FLAG_RECENT = 64;
	const FLAG_JUNK = 128;
	const FLAG_SENT = 256;
	
	const COLFLAG_HIERARCHY = 1;
	const COLFLAG_NONSUBSCRIBED = 2;
}

if (defined('BONGO_AUTO_LOAD')) {
	$base = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Bongo' . DIRECTORY_SEPARATOR;
	$all_bongo = array(
		'CommandStream.php',
		'CommandStreamResponse.php',
		'Document' . DIRECTORY_SEPARATOR . 'Contact.php',
		'Store' . DIRECTORY_SEPARATOR . 'Queries.php',
		'Store.php'
	);
	foreach ($all_bongo as $codefile) {
		require_once($base . $codefile);
	}
}
