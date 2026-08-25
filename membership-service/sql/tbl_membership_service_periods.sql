<?php
$tablename = 'tbl_membership_service_periods';
$options = array(
    'comment' => 'Provider-neutral membership periods and lifecycle state',
    'collate' => 'utf8_general_ci',
    'character_set' => 'utf8'
);
$fields = array(
    'id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'user_id' => array('type' => 'text', 'length' => 25, 'notnull' => TRUE),
    'tier_code' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'state' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'starts_at' => array('type' => 'timestamp', 'notnull' => TRUE),
    'ends_at' => array('type' => 'timestamp', 'notnull' => TRUE),
    'grace_ends_at' => array('type' => 'timestamp'),
    'source_type' => array('type' => 'text', 'length' => 96, 'notnull' => TRUE),
    'source_reference' => array('type' => 'text', 'length' => 191),
    'idempotency_key' => array('type' => 'text', 'length' => 191, 'notnull' => TRUE),
    'correlation_id' => array('type' => 'text', 'length' => 64, 'notnull' => TRUE),
    'created_at' => array('type' => 'timestamp', 'notnull' => TRUE),
    'updated_at' => array('type' => 'timestamp', 'notnull' => TRUE)
);
$tableIndexes = array(
    'membership_period_primary' => array(
        'primary' => TRUE,
        'fields' => array('id' => array())
    ),
    'membership_period_idempotency' => array(
        'unique' => TRUE,
        'fields' => array('idempotency_key' => array())
    ),
    'membership_period_user_time' => array(
        'fields' => array(
            'user_id' => array(), 'starts_at' => array(), 'ends_at' => array()
        )
    ),
    'membership_period_source' => array(
        'fields' => array('source_type' => array(), 'source_reference' => array())
    )
);
?>
