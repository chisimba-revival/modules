<?php
/** Provider-specific matching and mapping; never guesses a taxon or a missing trait. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class grassguideproviders extends ChisimbaObject
{
    public const HOSTS=['grassbase'=>'zenodo.org','kew'=>'species-ws.data.kew.org','gbif'=>'api.gbif.org','wikipedia'=>'en.wikipedia.org','inaturalist'=>'api.inaturalist.org'];
    public function init() {}
    protected function request(string $provider,string $path,array $query=[]): array
    {
        // A database provider lock spans each job, including this pacing interval.
        usleep(1100000);
        $url='https://'.self::HOSTS[$provider].$path.($query?'?'.http_build_query($query,'','&',PHP_QUERY_RFC3986):'');
        $r=$this->getObject('publicjsontransport','utilities')->get($url,array_values(self::HOSTS),'Chisimba-GrassGuide/0.004 (+https://github.com/chisimba-revival/modules; noncommercial botanical guide)');
        if (!$r['ok']) throw new GrassguideProviderException((int)$r['status'],(int)$r['retry_after']);
        return $r['data'];
    }
    public static function plain($value,int $limit=12000): string
    {
        if (!is_string($value)) return '';
        return mb_substr(trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u','',html_entity_decode(strip_tags($value),ENT_QUOTES|ENT_HTML5,'UTF-8'))),0,$limit);
    }
    private function source(string $provider,string $url,string $licence,string $citation): array
    { return ['provider'=>$provider,'url'=>$url,'licence'=>$licence,'citation'=>self::plain($citation,1500),'retrieved_at'=>gmdate('c')]; }
    private function field(array &$result,string $key,$value,array $source): void
    { if ($value!=='' && $value!==[]) { $result['fields'][$key]=$value; $result['sources'][$key]=$source; } }
    public function fetch(string $provider,array $species): array
    {
        if (!isset(self::HOSTS[$provider])) throw new InvalidArgumentException('Unknown provider');
        $result=['name'=>$species['scientific_name'],'fields'=>[],'sources'=>[],'media'=>[],'matched'=>false];
        return $this->$provider($species,$result);
    }
    private function grassbase(array $s,array $r): array
    {
        // Versioned, checksum-verified subset fetched through the Zenodo records API.
        // Bulk data is cached with the module rather than downloaded per species.
        static $cache=null;
        $cache ??= json_decode(file_get_contents(dirname(__DIR__).'/data/grassbase-lowveld.json'),true,64,JSON_THROW_ON_ERROR);
        $record=$cache['species'][$s['scientific_name']] ?? null;if(!$record)return $r;
        $r['matched']=true;$r['identifier']=$record['original_name'];$r['match']=$record['match'];$r['characters']=$record['characters'];
        $src=$this->source('grassbase','https://doi.org/'.$cache['doi'],$cache['licence'],$record['citation'].' Imported from the versioned local dataset cache.');
        foreach($record['fields'] as $key=>$value){$fieldSource=$src;if($key==='key_features')$fieldSource['citation'].=' Field highlights translated and selected from explicit structured characters against regional peers; not a definitive identification key.';$this->field($r,$key,$value,$fieldSource);}
        return $r;
    }
    private function kew(array $s,array $r): array
    {
        $x=$this->request('kew','/search',['q'=>$s['scientific_name'],'pageSize'=>20]);
        $hits=array_values(array_filter($x['searchResults']['results'] ?? [],fn($v)=>($v['scientificName'] ?? '')===$s['scientific_name'] && ($v['rank'] ?? '')==='species' && ($v['taxonomicStatus'] ?? '')==='Accepted'));
        if(count($hits)!==1)return $r;
        $id=$hits[0]['guid'] ?? ''; if(!preg_match('/^urn:lsid:ipni\.org:names:[0-9]+-[0-9]+$/D',$id))return $r;
        $r['matched']=true; $r['identifier']=$id;
        $src=$this->source('kew','https://species-ws.data.kew.org/species/'.rawurlencode($id),'CC-BY','Royal Botanic Gardens, Kew: species data portal (IPNI/WCVP taxonomy).');
        $this->field($r,'powo_id',$id,$src); $this->field($r,'authority',self::plain($hits[0]['author'] ?? '',250),$src);
        $this->field($r,'taxonomy_source',$src['citation'].' '.$src['url'].'; automatically matched '.$src['retrieved_at'],$src);
        $this->field($r,'taxonomy_checked',gmdate('Y-m-d'),$src);
        $detail=$this->request('kew','/species/'.rawurlencode($id));
        if(($detail['taxonConcept']['nameString'] ?? '')!==$s['scientific_name'])return $r;
        $names=[];foreach($detail['synonyms'] ?? [] as $v)if(!empty($v['nameString']))$names[]=self::plain($v['nameString'],250);
        $this->field($r,'synonyms',mb_substr(implode("\n",array_unique($names)),0,3000),$src);
        return $r;
    }
    private function gbif(array $s,array $r): array
    {
        $m=$this->request('gbif','/v1/species/match',['name'=>$s['scientific_name'],'strict'=>'true','kingdom'=>'Plantae']);
        if(($m['matchType'] ?? '')!=='EXACT' || ($m['canonicalName'] ?? '')!==$s['scientific_name'] || ($m['family'] ?? '')!=='Poaceae' || ($m['rank'] ?? '')!=='SPECIES')return $r;
        $id=(int)($m['acceptedUsageKey'] ?? $m['usageKey']);$r['matched']=true;$r['identifier']=$id;
        $x=$this->request('gbif','/v1/species/'.$id.'/descriptions',['limit'=>100]);
        foreach($x['results'] ?? [] as $v) {
            $licence=$v['license'] ?? ''; $language=strtolower($v['language'] ?? '');
            if(!in_array($language,['en','eng'],true) || !preg_match('~^https?://creativecommons.org/(licenses/(by|by-sa|by-nc|by-nc-sa)/(3\.0|4\.0)|publicdomain/zero/1\.0)/?$~D',$licence))continue;
            $type=strtolower($v['type'] ?? '');$key=match($type){'description','morphology','general','general description'=>'description','habitat','ecology'=>'habitat','diagnostic','diagnosis'=>'diagnostic',default=>null};
            if(!$key)continue;
            $src=$this->source('gbif','https://www.gbif.org/species/'.(int)($v['sourceTaxonKey'] ?? $id),$licence,($v['creator'] ?? '').' — '.($v['source'] ?? 'GBIF species descriptions'));
            if(empty($r['fields'][$key]))$this->field($r,$key,self::plain($v['description'] ?? '',$key==='habitat'?6000:12000),$src);
        }
        return $r;
    }
    private function wikipedia(array $s,array $r): array
    {
        $x=$this->request('wikipedia','/w/api.php',['action'=>'query','format'=>'json','prop'=>'extracts|info|revisions|pageprops','explaintext'=>1,'inprop'=>'url','rvprop'=>'ids','titles'=>$s['scientific_name'],'redirects'=>1]);
        $pages=array_values($x['query']['pages'] ?? []);if(count($pages)!==1)return $r;$p=$pages[0];
        // Redirects to a genus, disambiguation or another taxon are not species descriptions.
        if(isset($p['missing']) || isset($p['pageprops']['disambiguation']) || strcasecmp($p['title'] ?? '',$s['scientific_name'])!==0)return $r;
        $text=self::plain($p['extract'] ?? '',60000);if($text==='')return $r;
        $r['matched']=true;$r['identifier']=(int)$p['pageid'];
        $rev=(int)($p['revisions'][0]['revid'] ?? 0);
        $src=$this->source('wikipedia','https://en.wikipedia.org/'.($rev?'w/index.php?oldid='.$rev:'wiki/'.rawurlencode($p['title'])),'CC-BY-SA-4.0','Wikipedia contributors, '.$p['title'].'. Text excerpt; see source history for authors.');
        // Some upstream extracts contain escaped newlines; normalise only those separators.
        $text=str_replace('\\n',"\n",$text);
        $parts=preg_split('/\n\s*==+\s*([^=\n]+?)\s*==+\s*\n/u',$text,-1,PREG_SPLIT_DELIM_CAPTURE);
        $description=trim($parts[0] ?? '');$diagnostic='';$habitat='';
        for($i=1;$i<count($parts)-1;$i+=2) {
            $title=strtolower(trim($parts[$i]));$body=trim($parts[$i+1]);
            if(in_array($title,['description','botanical description','morphology'],true))$diagnostic.=$body."\n\n";
            if(in_array($title,['habitat','distribution and habitat','habitat and distribution','ecology'],true))$habitat.=$body."\n\n";
        }
        $this->field($r,'description',mb_substr(trim($description."\n\n".$diagnostic),0,12000),$src);
        $this->field($r,'diagnostic',mb_substr(trim($diagnostic),0,12000),$src);
        $this->field($r,'habitat',mb_substr(trim($habitat),0,6000),$src);
        // Explicit wording only. Ambiguous/negated statements remain unknown.
        $traitText=strtolower($diagnostic);$heads=[];
        foreach(['open_panicle'=>'open panicle','contracted_panicle'=>'contracted panicle','digitate'=>'digitate','single_raceme'=>'single raceme'] as $key=>$phrase)if(str_contains($traitText,$phrase) && !preg_match('/\b(not|never|without)\b/',$traitText))$heads[]=$key;
        $this->field($r,'heads',$heads,$src);
        if($heads)$this->field($r,'trait_source','Automatically extracted explicit inflorescence wording. '.$src['citation'].' '.$src['url'],$src);
        return $r;
    }
    /** Canonical iNaturalist photo identity; never infer it from an arbitrary numeric URL. */
    public static function photoId(array $media): string
    {
        if(preg_match('~^https://(?:www\.)?inaturalist\.org/photos/([1-9][0-9]{0,12})(?:[/?#].*)?$~D',$media['source_url']??'',$m))return $m[1];
        return '';
    }
    public static function samePhoto(array $a,array $b): bool
    {
        $id=self::photoId($a);return $id!==''?$id===self::photoId($b):(($a['image_url']??'')!==''&&$a['image_url']===($b['image_url']??''));
    }
    /** Exact photo membership only: an observation grade is not a taxon-wide confidence score. */
    private function photoGrades(array $media,int $taxon): array
    {
        $grades=[];$checked=gmdate('c');
        foreach($media as $m){$id=self::photoId($m);if($id!==''&&($m['state']??'')==='active')$grades[$id]=['photo_id'=>$id,'quality_grade'=>'unavailable','observations'=>[],'checked_at'=>$checked];}
        if(!$grades)return [];
        // Bounded lookup of the most-favourited observations; unmatched gallery photos stay unknown.
        $observations=[];
        for($page=1;$page<=4;$page++){
            $x=$this->request('inaturalist','/v1/observations',['taxon_id'=>$taxon,'photos'=>'true','per_page'=>25,'page'=>$page,'order_by'=>'votes','order'=>'desc','verifiable'=>'any']);
            $batch=$x['results']??[];$observations=array_merge($observations,$batch);if(count($batch)<25)break;
        }
        foreach($observations as $observation){
            $oid=(string)($observation['id']??'');$grade=$observation['quality_grade']??'';
            if(!preg_match('/^[1-9][0-9]{0,12}$/D',$oid)||!in_array($grade,['casual','needs_id','research'],true))continue;
            foreach($observation['photos']??[] as $photo){$id=(string)($photo['id']??'');if(!isset($grades[$id]))continue;
                $grades[$id]['observations'][$oid]=['id'=>$oid,'quality_grade'=>$grade];
            }
        }
        foreach($grades as &$value){$value['observations']=array_values($value['observations']);if(count($value['observations'])===1)$value['quality_grade']=$value['observations'][0]['quality_grade'];}
        return $grades;
    }
    private function inaturalist(array $s,array $r): array
    {
        $x=$this->request('inaturalist','/v1/taxa',['q'=>$s['scientific_name'],'rank'=>'species','is_active'=>'true','per_page'=>30]);
        $hits=array_values(array_filter($x['results'] ?? [],fn($v)=>($v['name'] ?? '')===$s['scientific_name'] && ($v['rank'] ?? '')==='species' && !empty($v['is_active']) && in_array(47434,$v['ancestor_ids'] ?? [],true)));
        if(count($hits)!==1)return $r;$id=(int)$hits[0]['id'];
        $x=$this->request('inaturalist','/v1/taxa/'.$id);$t=$x['results'][0] ?? [];
        if(($t['name'] ?? '')!==$s['scientific_name'] || empty($t['is_active']))return $r;
        $r['matched']=true;$r['identifier']=$id;
        foreach($t['taxon_photos'] ?? [] as $v) {
            $p=$v['photo'] ?? []; $licence=strtolower($p['license_code'] ?? '');
            if(!in_array($licence,['cc0','cc-by','cc-by-sa','cc-by-nc','cc-by-nc-sa'],true) || !empty($p['flags']))continue;
            $photo=(int)($p['id'] ?? 0);$url=$p['large_url'] ?? $p['medium_url'] ?? '';
            if(!$photo || !str_starts_with($url,'https://') || empty($p['attribution_name']))continue;
            $r['media'][]=['external_id'=>'inaturalist:'.$photo,'kind'=>'image','role'=>'other','state'=>'active','source_url'=>'https://www.inaturalist.org/photos/'.$photo,'image_url'=>$url,'youtube_id'=>'','caption'=>$s['scientific_name'].' — iNaturalist taxon photograph','alt_text'=>$s['scientific_name'].' (community-selected taxon photograph)','creator'=>self::plain($p['attribution_name'],300),'licence'=>$licence==='cc0'?'CC0-1.0':strtoupper($licence).'-4.0','rights_note'=>self::plain($p['attribution'] ?? '',1000).'\nRetrieved '.gmdate('c').'; taxon https://www.inaturalist.org/taxa/'.$id.'. Diagnostic view has not been classified.'];
            if(count($r['media'])>=6)break;
        }
        $r['photo_grades']=$this->photoGrades(array_merge($s['_existing_media']??[],$r['media']),$id);
        return $r;
    }
}
class GrassguideProviderException extends RuntimeException
{
    public function __construct(public int $status,public int $retryAfter) { parent::__construct('Provider HTTP '.$status); }
}
