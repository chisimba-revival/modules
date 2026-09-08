<?php
$tablename = 'tbl_registration_service_pending';
$options = array(
    'comment' => 'Unverified registration requests outside canonical identity',
    'collate' => 'utf8_general_ci',
    'character_set' => 'utf8'
);
$fields = array(
    'id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'username' => array('type' => 'text', 'length' => 255, 'notnull' => TRUE),
    'email_address' => array('type' => 'text', 'length' => 320, 'notnull' => TRUE),
    'first_name' => array('type' => 'text', 'length' => 50, 'notnull' => TRUE),
    'surname' => array('type' => 'text', 'length' => 50, 'notnull' => TRUE),
    'mobile_number' => array('type' => 'text', 'length' => 16, 'notnull' => TRUE),
    'identity_document_type' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'identity_document_number' => array('type' => 'text', 'length' => 128, 'notnull' => TRUE),
    'password_hash' => array('type' => 'text', 'length' => 255),
    'status' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'correlation_id' => array('type' => 'text', 'length' => 64, 'notnull' => TRUE),
    'expires_at' => array('type' => 'timestamp', 'notnull' => TRUE),
    'verified_at' => array('type' => 'timestamp'),
    'last_reminder_at' => array('type' => 'timestamp'),
    'provisioned_user_id' => array('type' => 'text', 'length' => 25),
    'payment_product_code' => array('type' => 'text', 'length' => 96),
    'created_at' => array('type' => 'timestamp', 'notnull' => TRUE),
    'updated_at' => array('type' => 'timestamp', 'notnull' => TRUE)
);
$tableIndexes = array(
    'registration_pending_primary' => array(
        'primary' => TRUE,
        'fields' => array('id' => array())
    ),
    'registration_pending_username' => array(
        'fields' => array('username' => array(), 'status' => array())
    ),
    'registration_pending_email' => array(
        'fields' => array('email_address' => array(), 'status' => array())
    ),
    'registration_pending_expiry' => array(
        'fields' => array('status' => array(), 'expires_at' => array())
    ),
    'registration_pending_correlation' => array(
        'fields' => array('correlation_id' => array())
    )
);
?>
