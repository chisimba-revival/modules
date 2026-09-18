<?php
/** Bounded text extraction using the existing ODT parser. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class workshopsource extends ChisimbaObject
{
    public function validate($text)
    {
        $text=(string)$text;
        if(!mb_check_encoding($text,'UTF-8'))throw new DomainException('source_encoding');
        if(preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/',$text))throw new DomainException('source_controls');
        $text=trim($text);
        if(mb_strlen($text,'UTF-8')<100)throw new DomainException('source_short');
        if(strlen($text)>5242880)throw new DomainException('source_large');
        return $text;
    }
    public function upload(array $upload)
    {
        if (($upload['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK || !is_uploaded_file($upload['tmp_name']??'')) throw new DomainException('upload_failed');
        return $this->file($upload['tmp_name'],$upload['name']??'');
    }
    public function file($path,$name)
    {
        if (!is_file($path) || filesize($path)>5242880) throw new DomainException('source_large');
        $ext=strtolower(pathinfo($name,PATHINFO_EXTENSION));
        if($ext==='txt')return $this->validate(file_get_contents($path));
        if($ext!=='odt')throw new DomainException('file_type');
        try {$document=$this->getObject('odtingestparser','ingestservice')->parse($path,['maxSourceBytes'=>5242880,'maxExpandedBytes'=>16777216,'maxArchiveEntries'=>200,'maxCompressionRatio'=>100,'maxImageBytes'=>2097152]);}
        catch(Throwable $e){throw new DomainException('file_invalid');}
        $parts=[];
        foreach($document['blocks'] as $block){
            if(isset($block['text']))$parts[]=$block['text'];
            foreach($block['items']??[] as $item)$parts[]=$item['text'];
            foreach($block['rows']??[] as $row)foreach($row as $cell)$parts[]=$cell['text'];
        }
        return $this->validate(implode("\n\n",$parts));
    }
}
