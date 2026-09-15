<?php
/** Public channel catalogue, separate from webinar history. @author Derek Keats */
$tablename='tbl_webinar_videos';
$options=['type'=>'innodb','comment'=>'Reviewed public channel video catalogue','collate'=>'utf8mb4_unicode_ci','charset'=>'utf8mb4'];
$fields=['id'=>['type'=>'text','length'=>32,'notnull'=>true],'channel_id'=>['type'=>'text','length'=>24,'notnull'=>true],'video_id'=>['type'=>'text','length'=>11,'notnull'=>true],'title'=>['type'=>'clob'],'duration'=>['type'=>'text','length'=>20],'position'=>['type'=>'integer'],'selection'=>['type'=>'text','length'=>10],'imported_at'=>['type'=>'timestamp']];
$tableIndexes=['webinar_video_identity'=>['unique'=>true,'fields'=>['id'=>[]]],'webinar_channel_video'=>['unique'=>true,'fields'=>['channel_id'=>[],'video_id'=>[]]]];
