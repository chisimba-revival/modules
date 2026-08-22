<?php
$tablename = 'tbl_entitlement_service_revocations';
$options = array(
    'comment' => 'Immutable entitlement grant revocations',
    'collate' => 'utf8_general_ci',
    'character_set' => 'utf8'
);
$fields = array(
    'id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'grant_id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'reason_code' => array('type' => 'text', 'length' => 96, 'notnull' => TRUE),
    'correlation_id' => array('type' => 'text', 'length' => 64, 'notnull' => TRUE),
    'revoked_at' => array('type' => 'timestamp', 'notnull' => TRUE),
    'revoked_by_type' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'revoked_by_id' => array('type' => 'text', 'length' => 191)
);
$tableIndexes = array(
    'entitlement_revocation_primary' => array(
        'primary' => TRUE,
        'fields' => array('id' => array())
    ),
    'entitlement_revocation_grant' => array(
        'unique' => TRUE,
        'fields' => array('grant_id' => array())
    ),
    'entitlement_revocation_time' => array(
        'fields' => array('revoked_at' => array())
    )
);
?>

