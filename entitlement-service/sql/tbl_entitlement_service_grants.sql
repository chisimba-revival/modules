<?php
$tablename = 'tbl_entitlement_service_grants';
$options = array(
    'comment' => 'Immutable source-attributed entitlement grants',
    'collate' => 'utf8_general_ci',
    'character_set' => 'utf8'
);
$fields = array(
    'id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'user_id' => array('type' => 'text', 'length' => 25, 'notnull' => TRUE),
    'entitlement_type' => array('type' => 'text', 'length' => 96, 'notnull' => TRUE),
    'resource_type' => array('type' => 'text', 'length' => 96, 'notnull' => TRUE),
    'resource_id' => array('type' => 'text', 'length' => 191, 'notnull' => TRUE),
    'source_type' => array('type' => 'text', 'length' => 96, 'notnull' => TRUE),
    'source_reference' => array('type' => 'text', 'length' => 191),
    'idempotency_key' => array('type' => 'text', 'length' => 191, 'notnull' => TRUE),
    'correlation_id' => array('type' => 'text', 'length' => 64, 'notnull' => TRUE),
    'metadata_json' => array('type' => 'clob'),
    'effective_at' => array('type' => 'timestamp', 'notnull' => TRUE),
    'expires_at' => array('type' => 'timestamp'),
    'granted_at' => array('type' => 'timestamp', 'notnull' => TRUE),
    'granted_by_type' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'granted_by_id' => array('type' => 'text', 'length' => 191)
);
$tableIndexes = array(
    'entitlement_grant_primary' => array(
        'primary' => TRUE,
        'fields' => array('id' => array())
    ),
    'entitlement_grant_idempotency' => array(
        'unique' => TRUE,
        'fields' => array('idempotency_key' => array())
    ),
    'entitlement_grant_lookup' => array(
        'fields' => array(
            'user_id' => array(),
            'entitlement_type' => array(),
            'resource_type' => array(),
            'resource_id' => array(),
            'effective_at' => array(),
            'expires_at' => array()
        )
    ),
    'entitlement_grant_source' => array(
        'fields' => array('source_type' => array(), 'source_reference' => array())
    )
);
?>

