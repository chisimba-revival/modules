<?php
$tablename = 'tbl_registration_service_tokens';
$options = array(
    'comment' => 'Hashed single-use registration and account recovery tokens',
    'collate' => 'utf8_general_ci',
    'character_set' => 'utf8'
);
$fields = array(
    'id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'purpose' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'subject_type' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'subject_id' => array('type' => 'text', 'length' => 191, 'notnull' => TRUE),
    'selector' => array('type' => 'text', 'length' => 24, 'notnull' => TRUE),
    'verifier_hash' => array('type' => 'text', 'length' => 64, 'notnull' => TRUE),
    'correlation_id' => array('type' => 'text', 'length' => 64, 'notnull' => TRUE),
    'expires_at' => array('type' => 'timestamp', 'notnull' => TRUE),
    'consumed_at' => array('type' => 'timestamp'),
    'superseded_at' => array('type' => 'timestamp'),
    'created_at' => array('type' => 'timestamp', 'notnull' => TRUE)
);
$tableIndexes = array(
    'registration_token_primary' => array(
        'primary' => TRUE,
        'fields' => array('id' => array())
    ),
    'registration_token_selector' => array(
        'unique' => TRUE,
        'fields' => array('selector' => array())
    ),
    'registration_token_subject' => array(
        'fields' => array(
            'purpose' => array(),
            'subject_type' => array(),
            'subject_id' => array(),
            'created_at' => array()
        )
    ),
    'registration_token_expiry' => array(
        'fields' => array('expires_at' => array(), 'consumed_at' => array())
    )
);
?>

