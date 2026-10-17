<?php
/* ----------- data class extends dbTable for tbl_discussion_temp_attachment------------*/
// security check - must be included in all scripts
if (!$GLOBALS['kewl_entry_point_run'])
    {
        die("You cannot view this page directly");
    }

/**
* Temporary Post Attachments Table
* This class stores records of attachments for posts whilst the post is being compiled
* @author Tohir Solomons
* @copyright (c) 2004 University of the Western Cape
* @package discussion
* @version 1
*/
class dbtempattachments extends dbTable
{

    /**
    * Constructor method to define the table
    */
    function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorHandler') {
        parent::init('tbl_discussion_temp_attachment');
    }
    
    /**
    * Method to get a list of attachment with all details
    *
    * @param string $id Temporary Post Id
    * @return array List of Attachments
    */
    function getList($id, $userId=null) {
        if ($userId===null) return array();
        return $this->selectPrepared('SELECT pending.id AS pending_id, files.* FROM tbl_discussion_temp_attachment AS pending INNER JOIN tbl_files AS files ON pending.attachment_id=files.id WHERE pending.temp_id=? AND pending.userid=? ORDER BY files.filename',array($id,$userId));
    }

    /**
    * Method to get a list of attachment record by providing the temporary post id
    *
    * @param string $id Temporary Post Id
    * @return array List of Attachments
    */
    function getQuickList($id, $userId=null) {
        if ($userId===null) return array();
        return $this->selectPrepared('SELECT * FROM tbl_discussion_temp_attachment WHERE temp_id=? AND userid=?',array($id,$userId));
    }

    /**
    * Method to save a temprary attachement
    *
    * @param string $temp_id: Temporary Id of the post before it is being posted
    * @param string $attachment_id: File Id of attachment
    * @param string $discussion_id: Discussion the attachment is related
    * @param string $userId: User Id of the person 
    * @param string $dateLastUpdated: Date attachment is added
    */
    function insertSingle($temp_id, $attachment_id, $userId, $dateLastUpdated)
    {
            return 
        $this->insert(array(
                'temp_id' => $temp_id,
                'attachment_id' => $attachment_id,
                'userid' => $userId,
                'datecreated' => date('Y-m-d H:i:s', $dateLastUpdated)));
    }
    
    /**
    * Method to Delete an Attachment
    * @param string $temp_id Record Id of the Temporary Post
    * @param string $attachment_id Record Id of the Attachment
    *
    */
    function deleteAttachment($temp_id, $attachment_id, $userId=null) {
        if ($userId===null) return false;
        return $this->executePrepared('DELETE FROM tbl_discussion_temp_attachment WHERE temp_id=? AND attachment_id=? AND userid=?',array($temp_id,$attachment_id,$userId));
    }

    /**
    * Method to delete the temporary records for attachments
    *
    * @param string $id Temporary Post Id
    */
    function deleteTemps($id, $userId=null) {
        if ($userId===null) return false;
        return $this->executePrepared('DELETE FROM tbl_discussion_temp_attachment WHERE temp_id=? AND userid=?',array($id,$userId));
    }


} #end of class
?>