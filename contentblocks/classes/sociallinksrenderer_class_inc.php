<?php
/** Render explicitly marked, editable link lists with trusted local icons. */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class sociallinksrenderer extends ChisimbaObject
{
    public function render($html)
    {
        $html=$this->getObject('richtextsanitizer','utilities')->cleanHtml($html);
        $doc=new DOMDocument('1.0','UTF-8');
        $previous=libxml_use_internal_errors(true);
        try{$doc->loadHTML('<?xml encoding="UTF-8"><html><body>'.$html.'</body></html>',LIBXML_NONET);}
        finally{libxml_clear_errors();libxml_use_internal_errors($previous);}
        $xpath=new DOMXPath($doc);
        $brands=['facebook.com'=>['facebook','Facebook'],'youtube.com'=>['youtube','YouTube'],
            'youtu.be'=>['youtube','YouTube'],'tiktok.com'=>['tiktok','TikTok'],
            'flickr.com'=>['flickr','Flickr'],'pexels.com'=>['pexels','Pexels']];
        foreach($xpath->query('//ul[@class="chisimba-social-links"]/li/a') as $link){
            $url=$link->getAttribute('href');$host=strtolower((string)parse_url($url,PHP_URL_HOST));
            if(!in_array(strtolower((string)parse_url($url,PHP_URL_SCHEME)),['http','https'],true)||$host==='')continue;
            $host=preg_replace('/^www\./','',$host);
            $brand=$brands[$host]??null;$icon=$brand?'brand-'.$brand[0]:'globe';
            $label=trim($link->textContent);
            if($label===''||preg_replace('/^www\./','',strtolower($label))===$host){
                $label=$brand?$brand[1].' — '.(parse_url($url,PHP_URL_PATH)?:'/'):$host;
            }
            while($link->firstChild)$link->removeChild($link->firstChild);
            $link->setAttribute('class','chisimba-icon-button');
            $link->setAttribute('aria-label',$label);$link->setAttribute('title',$label);
            $svg=new DOMDocument();$svg->loadXML($this->getObject('iconservice','ui')->render($icon,['decorative'=>true]),LIBXML_NONET);
            $link->appendChild($doc->importNode($svg->documentElement,true));
        }
        $out='';foreach($doc->getElementsByTagName('body')->item(0)->childNodes as $child)$out.=$doc->saveHTML($child);
        return $out;
    }
}
