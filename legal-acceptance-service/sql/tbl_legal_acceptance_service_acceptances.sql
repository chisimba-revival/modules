<?php
$tablename = 'tbl_legal_acceptance_service_acceptances';
$options = array(
    'comment' => 'Immutable versioned legal acceptance evidence',
    'collate' => 'utf8_general_ci',
    'character_set' => 'utf8'
);
$fields = array(
    'id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'subject_type' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'subject_id' => array('type' => 'text', 'length' => 191, 'notnull' => TRUE),
    'policy_key' => array('type' => 'text', 'length' => 96, 'notnull' => TRUE),
    'policy_version' => array('type' => 'text', 'length' => 64, 'notnull' => TRUE),
    'content_digest' => array('type' => 'text', 'length' => 64, 'notnull' => TRUE),
    'acceptance_method' => array('type' => 'text', 'length' => 48, 'notnull' => TRUE),
    'ip_address' => array('type' => 'text', 'length' => 45),
    'user_agent' => array('type' => 'text', 'length' => 512),
    'locale' => array('type' => 'text', 'length' => 32),
    'correlation_id' => array('type' => 'text', 'length' => 64, 'notnull' => TRUE),
    'accepted_at' => array('type' => 'timestamp', 'notnull' => TRUE),
    'recorded_at' => array('type' => 'timestamp', 'notnull' => TRUE)
);
$tableIndexes = array(
    'legal_acceptance_primary' => array(
        'primary' => TRUE,
        'fields' => array('id' => array())
    ),
    'legal_acceptance_exact_evidence' => array(
        'unique' => TRUE,
        'fields' => array(
            'subject_type' => array(),
            'subject_id' => array(),
            'policy_key' => array(),
            'policy_version' => array(),
            'content_digest' => array()
        )
    ),
    'legal_acceptance_subject_policy' => array(
        'fields' => array(
            'subject_type' => array(),
            'subject_id' => array(),
            'policy_key' => array(),
            'accepted_at' => array()
        )
    ),
    'legal_acceptance_correlation' => array(
        'fields' => array('correlation_id' => array())
    )
);
?>

