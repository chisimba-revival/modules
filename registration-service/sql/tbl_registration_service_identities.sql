<?php
$tablename = 'tbl_registration_service_identities';
$options = array(
    'comment' => 'Identity document type and number used for certificate evidence',
    'collate' => 'utf8_general_ci',
    'character_set' => 'utf8'
);
$fields = array(
    'id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'user_id' => array('type' => 'text', 'length' => 25, 'notnull' => TRUE),
    'document_type' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'document_number' => array('type' => 'text', 'length' => 128, 'notnull' => TRUE),
    'document_fingerprint' => array('type' => 'text', 'length' => 64, 'notnull' => TRUE),
    'created_at' => array('type' => 'timestamp', 'notnull' => TRUE),
    'updated_at' => array('type' => 'timestamp', 'notnull' => TRUE),
    'updated_by' => array('type' => 'text', 'length' => 25, 'notnull' => TRUE)
);
$tableIndexes = array(
    'registration_identity_primary' => array('primary' => TRUE, 'fields' => array('id' => array())),
    'registration_identity_user' => array('unique' => TRUE, 'fields' => array('user_id' => array())),
    'registration_identity_document' => array('fields' => array('document_fingerprint' => array()))
);
?>
