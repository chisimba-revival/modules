<?php
/** Bounded, lossless request planning for quiz source text. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class quizinputplan extends ChisimbaObject
{
    public function settings(array $values = [])
    {
        $settings=array_merge(['bankCount'=>30,'drawCount'=>10,'selection'=>'per_attempt'],$values);
        foreach(['bankCount','drawCount'] as $key){if(!is_int($settings[$key])&&!(is_string($settings[$key])&&ctype_digit($settings[$key])))throw new InvalidArgumentException('quiz_settings');$settings[$key]=(int)$settings[$key];}
        if($settings['bankCount']<10||$settings['bankCount']>100||$settings['drawCount']<1||$settings['drawCount']>$settings['bankCount']||!in_array($settings['selection'],['per_attempt','fixed'],true))throw new InvalidArgumentException('quiz_settings');
        return array_intersect_key($settings,array_flip(['bankCount','drawCount','selection']));
    }
    public function build($source, $requestedCount = null)
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
        $count=$requestedCount===null?max(10,count($chunks)):$requestedCount;
        if(!is_int($count)||$count<10||$count>100||$count<count($chunks))throw new InvalidArgumentException('quiz_count');
        $requests=[];foreach($chunks as $i=>$text){$remaining=intdiv($count,count($chunks))+($i<$count%count($chunks)?1:0);while($remaining>0){$part=min(10,$remaining);$requests[]=['source'=>$text,'count'=>$part];$remaining-=$part;}}
        return ['count'=>$count,'requests'=>$requests];
    }
}
