<?php
$tablename = 'tbl_certificate_service_assignments';
$options = array('comment' => 'Certificate configuration for consuming resources', 'collate' => 'utf8_general_ci', 'character_set' => 'utf8');
$fields = array(
    'id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'resource_type' => array('type' => 'text', 'length' => 64, 'notnull' => TRUE),
    'resource_id' => array('type' => 'text', 'length' => 191, 'notnull' => TRUE),
    'base_id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'signer_id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'status' => array('type' => 'text', 'length' => 16, 'notnull' => TRUE, 'default' => 'active'),
    'created_by' => array('type' => 'text', 'length' => 25, 'notnull' => TRUE),
    'date_created' => array('type' => 'timestamp', 'notnull' => TRUE),
    'date_updated' => array('type' => 'timestamp', 'notnull' => TRUE)
);
$tableIndexes = array(
    'certificate_service_assignments_primary' => array('primary' => TRUE, 'fields' => array('id' => array())),
    'certificate_service_assignments_resource' => array('unique' => TRUE, 'fields' => array('resource_type' => array(), 'resource_id' => array()))
);
?>
