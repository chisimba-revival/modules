<?php
/** Bounded, lossless request planning for quiz source text. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class quizinputplan extends ChisimbaObject
{
    public function build($source)
    {
        $source=trim((string)$source);$length=mb_strlen($source,'UTF-8');
        if($length<100||$length>300000)throw new InvalidArgumentException('quiz_input');
        $chunks=[];
        while(mb_strlen($source,'UTF-8')>12000){
            $piece=mb_substr($source,0,12000,'UTF-8');
            $cut=mb_strrpos($piece,' ',0,'UTF-8');if($cut===false||$cut<10000)$cut=12000;
            $chunks[]=mb_substr($source,0,$cut,'UTF-8');$source=mb_substr($source,$cut,null,'UTF-8');
        }
        if(mb_strlen(trim($source),'UTF-8')<100&&$chunks)$chunks[count($chunks)-1].=$source;else $chunks[]=$source;
        $count=max(10,count($chunks));if($count>30)throw new InvalidArgumentException('quiz_input');
        $requests=[];foreach($chunks as $i=>$text)$requests[]=['source'=>$text,'count'=>intdiv($count,count($chunks))+($i<$count%count($chunks)?1:0)];
        return ['count'=>$count,'requests'=>$requests];
    }
}
