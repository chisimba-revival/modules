<?php
$tablename = 'tbl_certificate_service_signers';
$options = array('comment' => 'Managed certificate signers', 'collate' => 'utf8_general_ci', 'character_set' => 'utf8');
$fields = array(
    'id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'name' => array('type' => 'text', 'length' => 191, 'notnull' => TRUE),
    'title' => array('type' => 'text', 'length' => 191),
    'signature_path' => array('type' => 'text', 'length' => 512),
    'status' => array('type' => 'text', 'length' => 16, 'notnull' => TRUE, 'default' => 'active'),
    'created_by' => array('type' => 'text', 'length' => 25, 'notnull' => TRUE),
    'date_created' => array('type' => 'timestamp', 'notnull' => TRUE),
    'date_updated' => array('type' => 'timestamp', 'notnull' => TRUE)
);
$tableIndexes = array(
    'certificate_service_signers_primary' => array('primary' => TRUE, 'fields' => array('id' => array()))
);
?>
