<?php
$tablename = 'tbl_certificate_service_issuances';
$options = array('comment' => 'Immutable certificate issuance snapshots', 'collate' => 'utf8_general_ci', 'character_set' => 'utf8');
$fields = array(
    'id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'certificate_number' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'assignment_id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'subject_user_id' => array('type' => 'text', 'length' => 25, 'notnull' => TRUE),
    'resource_type' => array('type' => 'text', 'length' => 64, 'notnull' => TRUE),
    'resource_id' => array('type' => 'text', 'length' => 191, 'notnull' => TRUE),
    'completion_reference' => array('type' => 'text', 'length' => 191, 'notnull' => TRUE),
    'snapshot_json' => array('type' => 'clob', 'notnull' => TRUE),
    'issued_at' => array('type' => 'timestamp', 'notnull' => TRUE),
    'issued_by_type' => array('type' => 'text', 'length' => 16, 'notnull' => TRUE, 'default' => 'service'),
    'issued_by_id' => array('type' => 'text', 'length' => 191, 'notnull' => TRUE)
);
$tableIndexes = array(
    'certificate_service_issuances_primary' => array('primary' => TRUE, 'fields' => array('id' => array())),
    'certificate_service_issuances_number' => array('unique' => TRUE, 'fields' => array('certificate_number' => array())),
    'certificate_service_issuances_once' => array('unique' => TRUE, 'fields' => array('assignment_id' => array(), 'subject_user_id' => array(), 'completion_reference' => array()))
);
?>
