<?php
/** Spoken assessment storage. @author Derek Keats */
$tablename = 'tbl_spoken_attempts';
$options = ['comment'=>'Private formative spoken assessment', 'collate'=>'utf8mb4_unicode_ci', 'charset'=>'utf8mb4'];
$fields = [
    'id' => ['type'=>'text','length'=>32],
    'activity_id' => ['type'=>'text','length'=>32],
    'contextcode' => ['type'=>'text','length'=>255],
    'userid' => ['type'=>'text','length'=>64],
    'state' => ['type'=>'text','length'=>32],
    'claim_token' => ['type'=>'text','length'=>32],
    'snapshot_json' => ['type'=>'clob'],
    'original_transcript' => ['type'=>'clob'],
    'approved_transcript' => ['type'=>'clob'],
    'feedback_json' => ['type'=>'clob'],
    'teacher_feedback' => ['type'=>'clob'],
    'teacher_id' => ['type'=>'text','length'=>64],
    'reflection' => ['type'=>'clob'],
    'error_code' => ['type'=>'text','length'=>80],
    'transcription_model' => ['type'=>'text','length'=>80],
    'feedback_model' => ['type'=>'text','length'=>80],
    'duration_seconds' => ['type'=>'integer'],
    'date_created' => ['type'=>'timestamp'],
    'date_updated' => ['type'=>'timestamp'],
];
$tableIndexes = [
    'tbl_spoken_attempts_pk'=>['unique'=>true,'fields'=>['id'=>[]]],
    'spoken_attempt_owner'=>['fields'=>['activity_id'=>[],'userid'=>[]]],
    'spoken_attempt_queue'=>['fields'=>['state'=>[],'date_created'=>[]]],
];
