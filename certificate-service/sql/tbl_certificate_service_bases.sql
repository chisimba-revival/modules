<?php
$tablename = 'tbl_certificate_service_bases';
$options = array('comment' => 'Reusable certificate presentation and issuer snapshots', 'collate' => 'utf8_general_ci', 'character_set' => 'utf8');
$fields = array(
    'id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'name' => array('type' => 'text', 'length' => 191, 'notnull' => TRUE),
    'organisation' => array('type' => 'text', 'length' => 191, 'notnull' => TRUE),
    'company_name' => array('type' => 'text', 'length' => 255, 'notnull' => TRUE),
    'company_location' => array('type' => 'text', 'length' => 255),
    'website_url' => array('type' => 'text', 'length' => 255),
    'primary_colour' => array('type' => 'text', 'length' => 7, 'notnull' => TRUE, 'default' => '#1f2937'),
    'accent_colour' => array('type' => 'text', 'length' => 7, 'notnull' => TRUE, 'default' => '#b49352'),
    'logo_path' => array('type' => 'text', 'length' => 512),
    'status' => array('type' => 'text', 'length' => 16, 'notnull' => TRUE, 'default' => 'active'),
    'created_by' => array('type' => 'text', 'length' => 25, 'notnull' => TRUE),
    'date_created' => array('type' => 'timestamp', 'notnull' => TRUE),
    'date_updated' => array('type' => 'timestamp', 'notnull' => TRUE)
);
$tableIndexes = array(
    'certificate_service_bases_primary' => array('primary' => TRUE, 'fields' => array('id' => array())),
    'certificate_service_bases_name' => array('unique' => TRUE, 'fields' => array('name' => array()))
);
?>
