<?php
/** Public provider data normalisation; no credentials or raw provider HTML reach templates. */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class photoprovider extends ChisimbaObject
{
    public function init(){}
    public static function url($value,array $hosts)
    {
        if(!is_string($value)||strlen($value)>2048)return '';
        $p=parse_url($value);if(!$p||($p['scheme']??'')!=='https'||isset($p['user'])||isset($p['pass'])||isset($p['port'])||!in_array(strtolower($p['host']??''),$hosts,true))return '';
        return $value;
    }
    public static function normalise($provider,array $photos,$profile='')
    {
        $out=[];
        foreach($photos as $photo){
            if(!is_array($photo))continue;
            if($provider==='pexels'){
                $owner=self::url($photo['photographer_url']??'',['www.pexels.com','pexels.com']);
                if($profile!==''&&rtrim(strtolower($owner),'/')!==rtrim(strtolower($profile),'/'))continue;
                $id=(string)($photo['id']??'');$title=$photo['alt']??'';$credit=$photo['photographer']??'';
                $link=self::url($photo['url']??'',['www.pexels.com','pexels.com']);
                $image=self::url($photo['src']['large']??$photo['src']['medium']??'',['images.pexels.com']);
            }elseif($provider==='flickr'){
                $link=self::url($photo['link']??'',['www.flickr.com','flickr.com']);
                if(!preg_match('~/photos/[^/]+/(\d+)/~',$link,$m))continue;$id=$m[1];
                $title=$photo['title']??'';$image=self::url($photo['media']['m']??'',['live.staticflickr.com']);
                // Flickr's documented medium suffix gives a larger derivative; original remains a source link.
                $image=preg_replace('/_m\.(jpg|png)$/','_z.$1',$image);
                $credit=preg_match('/\("(.*)"\)$/',$photo['author']??'',$m)?$m[1]:'';
                $author=(string)($photo['author_id']??'');$owner=preg_match('/^[0-9]+@N[0-9]+$/D',$author)?'https://www.flickr.com/photos/'.$author.'/':'';
            }else throw new InvalidArgumentException('Unsupported provider');
            if(!ctype_digit($id)||!$link||!$image||!$owner||!is_string($title)||!is_string($credit))continue;
            $out[$id]=['id'=>$id,'title'=>mb_substr(strip_tags($title),0,500),'credit'=>mb_substr(strip_tags($credit),0,200),'image'=>$image,'url'=>$link,'owner_url'=>$owner];
            if(count($out)>=160)break;
        }
        return array_values($out);
    }
    public function fetch($provider,array $settings,$key='')
    {
        if($provider==='flickr'){
            if(!preg_match('/^[0-9]+@N[0-9]+$/D',$settings['user']??'')||!ctype_digit((string)($settings['album']??'')))throw new RuntimeException('configuration');
            $data=$this->request('https://api.flickr.com/services/feeds/photoset.gne?'.http_build_query(['set'=>$settings['album'],'nsid'=>$settings['user'],'format'=>'json','nojsoncallback'=>1]));
            if(!is_array($data['items']??null))throw new RuntimeException('provider');
            return self::normalise('flickr',$data['items']);
        }
        if($provider!=='pexels'||$key===''||preg_match('/[\r\n]/',$key))throw new RuntimeException('configuration');
        $profile=self::url($settings['profile']??'',['www.pexels.com','pexels.com']);if(!$profile)throw new RuntimeException('configuration');
        $started=microtime(true);$found=[];$terms=array_slice(array_values(array_unique(array_filter(array_map('trim',explode(',',$settings['terms']??'nature'))))),0,14);
        foreach($terms as $term){for($page=1;$page<=2;$page++){
            if(microtime(true)-$started>30)throw new RuntimeException('provider');
            $data=$this->request('https://api.pexels.com/v1/search?'.http_build_query(['query'=>$term,'per_page'=>80,'page'=>$page]),$key);
            if(!is_array($data['photos']??null))throw new RuntimeException('provider');
            foreach(self::normalise('pexels',$data['photos'],$profile) as $item)$found[$item['id']]=$item;
            if(count($data['photos'])<80)break;
        }if(count($found)>=60)break;}
        usort($found,fn($a,$b)=>(int)$b['id']<=>(int)$a['id']);return array_slice(array_values($found),0,80);
    }
    protected function request($url,$key='')
    {
        $ch=curl_init($url);$body='';
        curl_setopt_array($ch,[CURLOPT_FOLLOWLOCATION=>false,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>12,CURLOPT_HTTPHEADER=>$key===''?['Accept: application/json']:['Accept: application/json','Authorization: '.$key],CURLOPT_WRITEFUNCTION=>static function($ch,$part)use(&$body){if(strlen($body)+strlen($part)>4000000)return 0;$body.=$part;return strlen($part);}]);
        $ok=curl_exec($ch);$status=curl_getinfo($ch,CURLINFO_RESPONSE_CODE);
        if($ok===false||$status!==200)throw new RuntimeException('provider');
        $data=json_decode($body,true,512,JSON_THROW_ON_ERROR);if(!is_array($data))throw new RuntimeException('provider');return $data;
    }
}
