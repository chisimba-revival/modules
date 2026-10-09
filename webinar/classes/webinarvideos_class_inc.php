<?php
/** Public recordings catalogue; requests never call YouTube or expose credentials. @author Derek Keats */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class webinarvideos extends dbTable
{
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler'){parent::init('tbl_webinar_videos',$pearDb,$errorCallback);}
    public function channel(){return (string)$this->getObject('dbsysconfig','sysconfig')->getValue('YOUTUBE_CHANNEL','webinar','');}
    public function catalogue()
    {
        $channel=$this->channel();if(!preg_match('/^UC[A-Za-z0-9_-]{22}$/D',$channel))return [];
        $db=$this->objEngine->getDbObj();$q=method_exists($db,'quoteSmart')?$db->quoteSmart($channel):$db->quote($channel);
        return $this->getArray('SELECT * FROM tbl_webinar_videos WHERE channel_id='.$q.' ORDER BY position,id')?:[];
    }
    public static function page($value,$total)
    {
        if(!is_scalar($value)||filter_var($value,FILTER_VALIDATE_INT)===false||(int)$value<1)throw new InvalidArgumentException('Invalid page');
        return min((int)$value,max(1,(int)ceil($total/6)));
    }
}
