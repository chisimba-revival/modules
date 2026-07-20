<?php

class dbhotelstags extends dbtable
{

    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorHandler')
    {
        parent::init('tbl_hotels_tags');
		$this->objUser = $this->getObject('user', 'security');
    }
    
    public function addStoryTags($storyId, $tags)
	{
		$tags = explode(',', $tags);
		
		$this->clearStoryTags($storyId);
		
		if ((is_countable($tags) ? count($tags) : 0) > 0) {
			foreach ($tags as $tag)
			{
				$tag = trim(stripslashes($tag));
				
				if ($tag != '') {
					$this->addTag($storyId, $tag);
				}
			}
		}
	}
	
	private function addTag($storyId, $tag)
	{
		return $this->insert(array(
				'storyid'=>$storyId, 
				'tag'=>$tag, 
				'creatorid' => $this->objUser->userId(),
				'datecreated' => strftime('%Y-%m-%d %H:%M:%S', time())
			));
	}
    
    public function getStoryTags($storyId)
    {
        $results = $this->getAll(' WHERE storyid=\''.$storyId.'\'');
        
        if ((is_countable($results) ? count($results) : 0) == 0) {
            return '';
        } else {
            $returnArray = array();
            
            foreach ($results as $result)
            {
                $returnArray[] = $result['tag'];
            }
            
            return $returnArray;
        }
    }
	
	public function clearStoryTags($storyId)
	{
		return $this->delete('storyid', $storyId);
	}

}
?>