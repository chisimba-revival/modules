<?php
$tablename = 'tbl_membership_service_admissions';
$options = array(
    'comment' => 'Reviewed private-course admission records and canonical grant state',
    'collate' => 'utf8_general_ci',
    'character_set' => 'utf8'
);
$fields = array(
    'id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'course_code' => array('type' => 'text', 'length' => 255, 'notnull' => TRUE),
    'user_id' => array('type' => 'text', 'length' => 25, 'notnull' => TRUE),
    'admission_mode' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'review_status' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'payment_status' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'payment_reference' => array('type' => 'text', 'length' => 191),
    'reason' => array('type' => 'clob'),
    'entitlement_grant_id' => array('type' => 'text', 'length' => 32),
    'student_membership_added' => array('type' => 'integer', 'notnull' => TRUE, 'default' => 0),
    'created_by' => array('type' => 'text', 'length' => 25, 'notnull' => TRUE),
    'admitted_by' => array('type' => 'text', 'length' => 25),
    'revoked_by' => array('type' => 'text', 'length' => 25),
    'correlation_id' => array('type' => 'text', 'length' => 64, 'notnull' => TRUE),
    'created_at' => array('type' => 'timestamp', 'notnull' => TRUE),
    'updated_at' => array('type' => 'timestamp', 'notnull' => TRUE),
    'admitted_at' => array('type' => 'timestamp'),
    'revoked_at' => array('type' => 'timestamp')
);
$tableIndexes = array(
    'membership_admission_primary' => array('primary' => TRUE, 'fields' => array('id' => array())),
    'membership_admission_course_user' => array('fields' => array('course_code' => array(), 'user_id' => array())),
    'membership_admission_review' => array('fields' => array('course_code' => array(), 'review_status' => array(), 'updated_at' => array()))
);
?>
