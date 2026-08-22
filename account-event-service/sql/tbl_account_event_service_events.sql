<?php
$tablename = 'tbl_account_event_service_events';
$options = array(
    'comment' => 'Append-only sanitized account events',
    'collate' => 'utf8_general_ci',
    'character_set' => 'utf8'
);
$fields = array(
    'id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'event_type' => array('type' => 'text', 'length' => 96, 'notnull' => TRUE),
    'subject_type' => array('type' => 'text', 'length' => 48, 'notnull' => TRUE),
    'subject_id' => array('type' => 'text', 'length' => 191, 'notnull' => TRUE),
    'actor_type' => array('type' => 'text', 'length' => 48, 'notnull' => TRUE),
    'actor_id' => array('type' => 'text', 'length' => 191),
    'outcome' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'reason_code' => array('type' => 'text', 'length' => 96),
    'correlation_id' => array('type' => 'text', 'length' => 64, 'notnull' => TRUE),
    'source_service' => array('type' => 'text', 'length' => 96, 'notnull' => TRUE),
    'metadata_json' => array('type' => 'clob'),
    'occurred_at' => array('type' => 'timestamp', 'notnull' => TRUE),
    'recorded_at' => array('type' => 'timestamp', 'notnull' => TRUE)
);
$tableIndexes = array(
    'account_event_primary' => array(
        'primary' => TRUE,
        'fields' => array('id' => array())
    ),
    'account_event_subject' => array(
        'fields' => array(
            'subject_type' => array(),
            'subject_id' => array(),
            'occurred_at' => array()
        )
    ),
    'account_event_correlation' => array(
        'fields' => array('correlation_id' => array())
    ),
    'account_event_type_time' => array(
        'fields' => array('event_type' => array(), 'occurred_at' => array())
    )
);
?>

