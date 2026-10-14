<?php
/** Retired raw-filesystem attachment API. Stored data is preserved for migration. */
if(empty($GLOBALS['kewl_entry_point_run']))die('You cannot view this page directly');
class attachments extends ChisimbaObject
{
    public function init(){}
    public function listFiles($id){return [];}
    public function uploadFile($id){throw new RuntimeException('calendar_attachments_retired');}
    public function deleteFile($id,$filename){throw new RuntimeException('calendar_attachments_retired');}
    public function deleteAllFiles($id){throw new RuntimeException('calendar_attachments_retired');}
    public function transfer($tempId,$event){throw new RuntimeException('calendar_attachments_retired');}
}
