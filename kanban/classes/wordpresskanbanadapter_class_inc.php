<?php
/** Deterministic private migration; refuses unknown owners, sharing and lossy field conversion. */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class wordpresskanbanadapter extends ChisimbaObject
{
    public function init(){}
    public static function convert(array $source,array $owners,$namespace)
    {
        if(!$namespace||!empty($source['project_shares']))throw new InvalidArgumentException('Source namespace and explicit sharing review required');
        $out=['boards'=>[],'tasks'=>[],'subtasks'=>[]];$boards=[];$tasks=[];$ids=[];
        $id=function($kind,$value)use($namespace,&$ids){$v=(string)$value;if(!ctype_digit($v)||isset($ids[$kind.'|'.$v]))throw new InvalidArgumentException('Invalid or duplicate source identifier');$ids[$kind.'|'.$v]=true;return substr(hash('sha256',$namespace.'|'.$kind.'|'.$v),0,32);};
        $text=function($value,$max){$value=(string)($value??'');if(mb_strlen($value)>$max||preg_match('/<[a-zA-Z][^>]*>/',$value))throw new InvalidArgumentException('Text requires explicit conversion review');return $value;};
        $dates=function($row){foreach(['created_at','updated_at'] as $k)if(!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/D',$row[$k]??''))throw new InvalidArgumentException('Invalid source date');return ['datecreated'=>$row['created_at'],'datemodified'=>$row['updated_at']];};
        foreach($source['projects'] as $p){$owner=$owners[(string)$p['owner_id']]??'';if(!$owner||!in_array($p['visibility'],['private','shared'],true))throw new InvalidArgumentException('Owner or visibility needs review');$key=$id('project',$p['id']);$boards[(string)$p['id']]=$key;$out['boards'][]=['id'=>$key,'scopetype'=>'personal','scopeid'=>$owner,'ownerid'=>$owner,'title'=>$text($p['title'],255),'description'=>$text($p['description'],10000),'isarchived'=>(int)$p['is_archived'],'sortorder'=>(int)$p['sort_order']]+$dates($p);}
        foreach($source['tasks'] as $p){$board=$boards[(string)$p['project_id']]??'';if(!$board||!in_array($p['status'],['not_started','in_progress','completed'],true))throw new InvalidArgumentException('Orphan task or unknown status');$key=$id('task',$p['id']);$tasks[(string)$p['id']]=$key;$out['tasks'][]=['id'=>$key,'boardid'=>$board,'title'=>$text($p['title'],255),'description'=>$text($p['description'],10000),'notes'=>$text($p['notes'],10000),'status'=>$p['status'],'sortorder'=>(int)$p['sort_order']]+$dates($p);}
        foreach($source['subtasks'] as $p){$task=$tasks[(string)$p['task_id']]??'';if(!$task||!in_array((string)$p['is_completed'],['0','1'],true))throw new InvalidArgumentException('Orphan or invalid subtask');$out['subtasks'][]=['id'=>$id('subtask',$p['id']),'taskid'=>$task,'title'=>$text($p['title'],10000),'iscompleted'=>(int)$p['is_completed'],'sortorder'=>(int)$p['sort_order']]+$dates($p);}
        return $out;
    }
}
