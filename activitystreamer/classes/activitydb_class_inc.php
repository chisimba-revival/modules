<?php

/**
 * Activity Table
 * 
 * This class contains a list of all db functions for the Activity module
 * 
 * PHP version unknow
 * 
 * This program is free software; you can redistribute it and/or modify 
 * it under the terms of the GNU General Public License as published by 
 * the Free Software Foundation; either version 2 of the License, or 
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful, 
 * but WITHOUT ANY WARRANTY; without even the implied warranty of 
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the 
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License 
 * along with this program; if not, write to the 
 * Free Software Foundation, Inc., 
 * 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 * 
 

/* ----------- data class extends dbTable for tbl_blog------------*/
// security check - must be included in all scripts

/**
 * Description for $GLOBALS
 * @global integer $GLOBALS['kewl_entry_point_run']
 * @name   $kewl_entry_point_run
 */
if (!$GLOBALS['kewl_entry_point_run']) {
    die("You cannot view this page directly");
}



class activitydb extends dbTable
{
    private $objUser;
    /**
     * Constructor method to define the table
     */
    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorHandler') 
    {
        parent::init('tbl_activity');
        $this->objUser = $this->getObject('user', 'security');        
       
    }
    
    /**
     * Insert an activity to the database
     *
     * @param object $notification
     * @return string
     */
    
    public function insertPost($notification)
    {
    	
    	$content = $notification->getNotificationInfo();
    	if(!array_key_exists('contextcode',$content))
    	{
    		$content['contextcode'] = null;
    	}
    	$userId = $this->objUser->userId();
    	if(empty($userId)){
    	 if(!empty($content['author'])){
     	 $userId = $content['author'];
    	 }elseif(!empty($content['createdby'])){
    	  $userId = $content['createdby'];
    	 }else{
    	  $userId = Null;
    	 } 
    	}else{
    	 $userId = $this->objUser->userId();
    	}
    	$messageId = $this->insert(array(
                'module' => $notification->getNotificationName(),
                'description' => ($content['description'] ?? ''),
                'title' => ($content['title'] ?? ''),
                'createdon' => $this->now(),
                'createdby' => $userId,
                'contextcode' => $content['contextcode'],
                'link'        => ($content['link'] ?? ''),
            ));
            
       return $messageId;
    }
    
    /**
     * Get the latest activities
     *
     * @param unknown_type $limit
     * @return unknown
     */
    public function getActivities($filter = null, $limit = 10)
    {
        // Raw SQL fragments are no longer accepted by this legacy helper.
        if ($filter !== null && $filter !== '') throw new InvalidArgumentException('Use the scoped activity browser.');
        return $this->getObject('activitybrowser','activitystreamer')->browse('site', [], (int)$limit)['rows'];
    }
    public function getSiteActivities($limit = 10)
    {
        return $this->getActivities(null, $limit);
    }
}
