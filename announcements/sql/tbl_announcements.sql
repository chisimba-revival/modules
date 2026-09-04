<?php
/**
* Database Table Announcements
* @author Joel Kimilu
* @copyright 2008 University of the Western Cape
*/

//Chisimba definition
$tablename = 'tbl_announcements';

//Options line for comments, encoding and character set
$options = array('comment' => 'Used to store your announcements list', 'collate' => 'utf8_general_ci', 'character_set' => 'utf8');

$fields = array(
	'id' => array(
		'type' => 'text',
		'length' => 32,
	),
	'title' => array(
		'type' => 'text',
		'length' => 255,
	),
	'message' => array(
		'type' => 'text'
	),
	'createdon' => array(
		'type' => 'timestamp',
	),
	'createdby' => array(
		'type' => 'integer',
		'length' => 10,
	),

	'contextid' => array(
		'type' => 'text',
		'length' => 32,
	),
	'announcement_type' => array('type' => 'text', 'length' => 32, 'default' => 'general'),
	'audience' => array('type' => 'text', 'length' => 32, 'default' => 'everyone'),
	'resource_url' => array('type' => 'text', 'length' => 2048),
	'publish_at' => array('type' => 'timestamp'),
	'expires_at' => array('type' => 'timestamp'),
	'show_in_latest' => array('type' => 'integer', 'length' => 1, 'default' => 0)
	
);

?>
