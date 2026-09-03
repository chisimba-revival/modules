<?php

// security check - must be included in all scripts
if (!$GLOBALS['kewl_entry_point_run']) {
    die("You cannot view this page directly");
}

/**
* Topic Subscription Class
*
* This class keeps track of all the topics users have subscribed to
* Based on their subscription, email notifications are sent to them.
*
* @author Tohir Solomons
* @copyright (c) 2004 University of the Western Cape
* @package discussion
* @version 1
*/
class dbtopicsubscriptions extends dbtable
{
    
    /**
    * Constructor
    */
    function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorHandler')
    {
        parent::init('tbl_discussion_subscribe_topic');
    }
		
	/**
    * Method to find out how many topics (in a discussion) a user is subscribed to by providing the discussion_id and userid
    *
    * @param string $discussion_id: discussion to get topics from
    * @param string $userId: User Id of the person 
    *
    * @return integer Number of topics user is subscribed to
    */
    function getNumTopicsSubscribed($discussion_id, $userId)
	{
        $sql = ' SELECT count( tbl_discussion_subscribe_topic.id ) AS subscribecount
        FROM tbl_discussion_subscribe_topic
        INNER JOIN tbl_discussion_topic ON ( tbl_discussion_subscribe_topic.topic_id = tbl_discussion_topic.id ) 
        WHERE tbl_discussion_topic.discussion_id = "'.$discussion_id.'" AND tbl_discussion_subscribe_topic.userid = "'.$userId.'"';
        
        $number = $this->getArray($sql);
        
        return  $number[0]['subscribecount'];
    }
    
    function subscribeUserToTopic($topic_id, $userId)
    {
        return $this->insert(array(
            'topic_id'=>$topic_id, 
            'userid'=>$userId, 
            'external'=>'Y', 
            'datecreated'=>strftime('%Y-%m-%d %H:%M:%S', time())
        ));
    }
    
    /**
    * Method to find out if a user is subscribed to a discussion
    *
    * @param string $discussion_id: discussion to get topics from
    * @param string $userId: User Id of the person 
    *
    * @return integer Number of topics user is subscribed to
    */
    function isSubscribedToTopic($topic_id, $userId)
    {
        $sql = 'WHERE topic_id = "'.$topic_id.'" AND userid = "'.$userId.'"'; 
        
        $number = $this->getRecordCount($sql);
        
        if ($number > 0) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    function getUsersSubscribedTopic($topic_id)
    {
        $sql = 'SELECT DISTINCT emailAddress FROM tbl_discussion_subscribe_topic INNER JOIN tbl_users ON ( tbl_discussion_subscribe_topic.userid = tbl_users.userid ) WHERE topic_id = "'.$topic_id.'"';
        return $this->getArray($sql);
    }

    /** Return user identifiers subscribed to a topic for notification delivery. */
    public function recipientUserIds($topicId)
    {
        $rows = $this->getAll(' WHERE topic_id=' . $this->quoteValue($topicId));
        return array_values(array_unique(array_filter(array_column((array) $rows, 'userid'))));
    }

    /** Quote one identifier through the active database adapter. */
    private function quoteValue($value)
    {
        $db = $this->objEngine->getDbObj();
        return method_exists($db, 'quoteSmart') ? $db->quoteSmart((string) $value)
            : "'" . str_replace("'", "''", (string) $value) . "'";
    }
    
    function unsubscribeUserFromTopic($userId,$topic_id){
            $sql = "DELETE FROM tbl_discussion_subscribe_topic WHERE userid='{$userId}' AND topic_id='{$topic_id}'";
            return $this->query($sql);
    }
    
}
?>
