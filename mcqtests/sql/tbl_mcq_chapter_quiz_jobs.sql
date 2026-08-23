<?php
/** Durable background jobs for AI-generated chapter quiz previews. */
$tablename = 'tbl_mcq_chapter_quiz_jobs';
$options = array(
    'comment' => 'Durable AI chapter quiz generation jobs',
    'collate' => 'utf8_general_ci',
    'character_set' => 'utf8'
);
$fields = array(
    'id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'contextcode' => array('type' => 'text', 'length' => 255, 'notnull' => TRUE),
    'userid' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'status' => array('type' => 'text', 'length' => 20, 'notnull' => TRUE),
    'chapter_ids' => array('type' => 'clob', 'notnull' => TRUE),
    'result_json' => array('type' => 'clob'),
    'error_json' => array('type' => 'clob'),
    'progress_total' => array('type' => 'integer', 'notnull' => TRUE, 'default' => 0),
    'progress_completed' => array('type' => 'integer', 'notnull' => TRUE, 'default' => 0),
    'current_chapter' => array('type' => 'text', 'length' => 255),
    'date_created' => array('type' => 'timestamp', 'notnull' => TRUE),
    'date_updated' => array('type' => 'timestamp', 'notnull' => TRUE),
    'date_completed' => array('type' => 'timestamp')
);
$tableIndexes = array(
    'mcq_chapter_quiz_jobs_primary' => array('primary' => TRUE, 'fields' => array('id' => array())),
    'mcq_chapter_quiz_jobs_owner' => array('fields' => array('userid' => array(), 'contextcode' => array())),
    'mcq_chapter_quiz_jobs_status' => array('fields' => array('status' => array(), 'date_updated' => array()))
);
?>
