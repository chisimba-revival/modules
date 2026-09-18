<?php
/** Text and standards-based ODT exports, without embedded source or active content. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class workshopexport extends ChisimbaObject
{
    public function disposition(array $set,$format,$answers=false,$suffix=null)
    {
        $name=$set['title'];
        // Keep Unicode titles, removing only unsafe filename characters.
        $name=preg_replace('~[\\x00-\\x1f\\x7f/\\\\:*?"<>|]+~u','-', $name);
        $name=trim(mb_substr($name,0,160,'UTF-8')," .");
        if($name==='')$name='questions';
        $name.=($suffix??($answers?'-answer-key':'-questions')).'.'.$format;
        $fallback=preg_replace('/[^A-Za-z0-9 ._-]/','_', $name);
        return 'attachment; filename="'.$fallback.'"; filename*=UTF-8\'\''.rawurlencode($name);
    }
    public function lines(array $set,$answers=false)
    {
        $r=$this->getObject('workshoprenderer');
        $lines=[$set['title'],$r->text($answers?'answer_key':'question_paper'),''];
        if(empty($set['reviewed']))$lines[]=$r->text('draft_notice');
        $questions=array_values(array_filter(json_decode($set['questions_json'],true,512,JSON_THROW_ON_ERROR),static fn($q)=>$q['included']??true));
        if(!$questions)throw new DomainException('none_included');
        foreach($questions as $i=>$q){
            $lines[]=($i+1).'. '.$q['stem'];
            if($answers){$lines[]=chr(65+$q['correctIndex']).'. '.$q['options'][$q['correctIndex']];$lines[]=$r->text('source_basis').': '.$q['sourceBasis'];}
            else foreach($q['options'] as $j=>$option)$lines[]=chr(65+$j).'. '.$option;
            $lines[]='';
        }
        return $lines;
    }
    public function text(array $set,$answers=false) { return implode("\n",$this->lines($set,$answers))."\n"; }
    public function odt(array $set,$answers=false)
    {
        return $this->odtLines($this->lines($set,$answers));
    }
    /** Shared ODT packaging for sets and assembled exams. */
    public function odtLines(array $lines,array $paragraphStyles=[])
    {
        $path=tempnam(sys_get_temp_dir(),'mcq-odt-');
        try {
            $zip=new ZipArchive();
            if($zip->open($path,ZipArchive::OVERWRITE)!==true)throw new RuntimeException('export_failed');
            $zip->addFromString('mimetype','application/vnd.oasis.opendocument.text');
            $zip->setCompressionName('mimetype',ZipArchive::CM_STORE);
            $xml='<?xml version="1.0" encoding="UTF-8"?><office:document-content xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0" xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0" xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0" office:version="1.2"><office:automatic-styles><style:style style:name="Title" style:family="paragraph"><style:paragraph-properties fo:keep-with-next="always"/><style:text-properties fo:font-size="18pt" fo:font-weight="bold"/></style:style><style:style style:name="Keep" style:family="paragraph"><style:paragraph-properties fo:keep-with-next="always" fo:keep-together="always"/></style:style><style:style style:name="Heading" style:family="paragraph"><style:paragraph-properties fo:keep-with-next="always"/><style:text-properties fo:font-size="13pt" fo:font-weight="bold"/></style:style></office:automatic-styles><office:body><office:text>';
            foreach($lines as $index=>$line){
                $style=$paragraphStyles[$index]??($index===0?'Title':'');
                if(!in_array($style,['','Title','Keep','Heading'],true))throw new RuntimeException('export_failed');
                $xml.='<text:p'.($style!==''?' text:style-name="'.$style.'"':'').'>'.htmlspecialchars($line,ENT_XML1|ENT_QUOTES,'UTF-8').'</text:p>';
            }
            $xml.='</office:text></office:body></office:document-content>';
            $zip->addFromString('content.xml',$xml);
            $zip->addFromString('styles.xml','<?xml version="1.0" encoding="UTF-8"?><office:document-styles xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0" xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0" office:version="1.2"><office:styles><style:default-style style:family="paragraph"><style:paragraph-properties fo:margin-bottom="0.15cm"/><style:text-properties fo:font-size="11pt" fo:font-family="Liberation Sans"/></style:default-style></office:styles><office:automatic-styles><style:page-layout style:name="Page"><style:page-layout-properties fo:page-width="21cm" fo:page-height="29.7cm" fo:margin="2cm"/></style:page-layout></office:automatic-styles><office:master-styles><style:master-page style:name="Standard" style:page-layout-name="Page"/></office:master-styles></office:document-styles>');
            $zip->addFromString('META-INF/manifest.xml','<?xml version="1.0" encoding="UTF-8"?><manifest:manifest xmlns:manifest="urn:oasis:names:tc:opendocument:xmlns:manifest:1.0" manifest:version="1.2"><manifest:file-entry manifest:full-path="/" manifest:media-type="application/vnd.oasis.opendocument.text"/><manifest:file-entry manifest:full-path="content.xml" manifest:media-type="text/xml"/><manifest:file-entry manifest:full-path="styles.xml" manifest:media-type="text/xml"/></manifest:manifest>');
            if(!$zip->close())throw new RuntimeException('export_failed');
            return file_get_contents($path);
        } finally {if(is_file($path))unlink($path);}
    }
}
