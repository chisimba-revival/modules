<?php
/** Compatibility JSON reads using the same policy as native blocks. @author Derek Keats
 * Original utilities by Paul Mungai; GNU GPL version 2 or later.
 */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class activityutilities extends ChisimbaObject
{
    public function jsonListActivity($start = 0, $limit = 5) { return $this->listing('site',$start,$limit); }
    public function jsonCourseActivies($start = 0, $limit = 5) { return $this->listing('pages',$start,$limit); }
    private function listing(string $mode, $start, $limit): string
    {
        $size=max(1,min(100,is_scalar($limit)?(int)$limit:5));
        $offset=max(0,is_scalar($start)?(int)$start:0);
        $data=$this->getObject('activitybrowser','activitystreamer')->browse($mode,['page'=>intdiv($offset,$size)+1],$size);
        $rows=$data['rows'];
        if($mode==='pages') foreach($rows as &$row) {
            $row['datecreated']=$row['createdon'];
            $row['contextpath']='module=contextcontent&action=viewpage&id='.rawurlencode($row['id']);
        }
        return json_encode(['totalCount'=>$data['total'],'activities'=>$rows],JSON_INVALID_UTF8_SUBSTITUTE);
    }
}
