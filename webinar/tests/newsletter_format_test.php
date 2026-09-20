<?php
/** Labelled multipart newsletter and real shared-thumbnail checks. No mail delivery. @author Derek Keats */
if(PHP_SAPI!=='cli')exit(1);
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {
 function getObject($n,$m=''){return $GLOBALS['services'][$n];}
 function loadClass($n,$m=''){}
 function getResourcePath($f,$m){return getenv('CHISIMBA_FRAMEWORK_ROOT').'/app/core_modules/'.$m.'/resources/'.$f;}
 function loadResize(){require getenv('CHISIMBA_FRAMEWORK_ROOT').'/app/core_modules/files/classes/imageresize_class_inc.php';}
}
class dbTable extends ChisimbaObject{}
foreach(['webinarschedule','webinarannouncements','webinaremailimage'] as $n)require __DIR__.'/../classes/'.$n.'_class_inc.php';
require __DIR__.'/../../audience/classes/audiencecampaigns_class_inc.php';
require getenv('CHISIMBA_FRAMEWORK_ROOT').'/app/core_modules/filemanager/classes/filedelivery_class_inc.php';
require getenv('CHISIMBA_FRAMEWORK_ROOT').'/app/core_modules/filemanager/classes/emailthumbnail_class_inc.php';
(new ChisimbaObject)->loadResize();
function check($v,$why){if(!$v)throw new RuntimeException($why);}
$dir=getenv('NEWSLETTER_PREVIEW_DIR')?:sys_get_temp_dir().'/newsletter-format-'.bin2hex(random_bytes(5));
if(!is_dir($dir))mkdir($dir,0700,true);
$source=$dir.'/banner.png';$image=imagecreatetruecolor(1200,400);imagefill($image,0,0,imagecolorallocate($image,40,90,60));imagestring($image,5,45,150,'Birds and islands - synthetic banner',imagecolorallocate($image,255,255,255));for($x=0;$x<1200;$x+=5)for($y=0;$y<400;$y+=5)imagesetpixel($image,$x,$y,imagecolorallocate($image,($x*31)%255,($y*17)%255,($x+$y)%255));imagepng($image,$source);
$config=new class($dir){function __construct(public $base){}function getSiteRoot(){return 'http://127.0.0.1:18764/';}function getcontentBasePath(){return $this->base;}function getcontentPath(){return $this->base;}};
$file=['id'=>'fixture-banner','path'=>'banner.png','filefolder'=>'users/fixture','filename'=>'banner.png'];
$files=new class($file){function __construct(public $file){}function getFile($id){return $id==='fixture-banner'?$this->file:null;}function getFileMimetype($id){return 'image/png';}};
$policy=new class{public $allowed=true;function mayRead($file){return $this->allowed;}};
$parts=new class{function getExtension($f){return pathinfo($f,PATHINFO_EXTENSION);}};
$mkdir=new class{function mkdirs($p){return is_dir($p)||mkdir($p,0700,true);}};
$clean=new class{function cleanUpUrl($p){return $p;}};
$resize=new imageresize();$resize->objFileParts=$parts;
// Explicit inherited state declaration avoids a legacy dynamic-property warning in this harness.
$thumbnailService=new emailthumbnail();
$GLOBALS['services']=['altconfig'=>$config,'dbfile'=>$files,'filereadpolicy'=>$policy,'mkdir'=>$mkdir,'imageresize'=>$resize,'fileparts'=>$parts,'cleanurl'=>$clean,'emailthumbnail'=>$thumbnailService];
$record=['id'=>str_repeat('a',32),'kind'=>'webinar','status'=>'published','title'=>'Birds &amp; islands <script>alert(1)</script>','presented_at'=>'2099-10-15 19:00:00','payload'=>json_encode(['timezone'=>'Africa/Johannesburg','registration_open'=>true,'image_file_id'=>'fixture-banner','speakers'=>[]])];
$imageService=new webinaremailimage();$GLOBALS['services']['webinaremailimage']=$imageService;
$thumb=$imageService->thumbnail($record);check($thumb&&$thumb['width']===600&&$thumb['height']===200,'Real thumbnail resized to 600 pixels without cropping');
check(filedelivery::derivativeId('filemanager_thumbnails/large/standard_fixture-banner.jpg')==='fixture-banner','Derivative preserves original file identity');
check(filesize($dir.'/filemanager_thumbnails/large/standard_fixture-banner.jpg')<filesize($source),'Reduced file size');
$policy->allowed=false;check($imageService->thumbnail($record)===null,'Denied banner omitted');$policy->allowed=true;
$draft=$record;$draft['status']='draft';check($imageService->thumbnail($draft)===null,'Draft banner omitted');
$external=$record;$external['payload']=json_encode(['image'=>'https://untrusted.example/banner.png']);check($imageService->thumbnail($external)===null,'No arbitrary external fetch or oversized fallback');
$labels=['email_title'=>'Title:','email_datetime'=>'Date and time:','email_register'=>'Register at:'];
$GLOBALS['services']['webinarrenderer']=new class($labels){function __construct(public $labels){}function text($k){return $this->labels[$k]??$k;}};
$GLOBALS['services']['webinarstore']=new class($record){function __construct(public $record){}function published($k){return [$this->record];}};
$announcements=new webinarannouncements();$GLOBALS['services']['webinarannouncements']=$announcements;
$text=$announcements->upcomingText();$html=$announcements->upcomingHtml();
foreach($labels as $label)check(str_contains($text,$label)&&str_contains($html,$label),'Labels in both alternatives');
check(strpos($html,'<img')<strpos($html,'<strong>Title:'),'Banner precedes title');check(!str_contains($html,'<script>'),'Escape hostile titles');check($announcements->upcomingText(['different'])===''&&$announcements->upcomingHtml(['different'])==='','Event scope respected');
$GLOBALS['services']['dbsysconfig']=new class{function getValue($k,$m,$d=''){return 'Support us: https://example.test/support';}};
$campaigns=new audiencecampaigns();$payload=['greeting'=>'Hello {FIRSTNAME},','body'=>'A message <img src=x onerror=alert(1)>','upcoming'=>true,'support'=>true];$row=['payload'=>json_encode($payload)];
$composed=$campaigns->composeHtml($row);check(!str_contains($composed,'<img src=x'),'Pasted HTML is escaped');check(str_contains($composed,'href="https://example.test/support"'),'Text URLs clickable');
$payload['preview_html']=$composed;$payload['preview']=$campaigns->compose($row);$GLOBALS['services']['webinarstore']->record['title']='Changed title';
check($campaigns->composeHtml(['payload'=>json_encode($payload)])===$composed,'Saved HTML frozen');
check($campaigns->composeHtml(['payload'=>json_encode(['preview'=>'Reviewed old text','upcoming'=>true])])==='<p>Reviewed old text</p>','Old draft remains frozen until resaved');
$GLOBALS['services']['webinarstore']->record['title']='Birds and islands';$payload=['greeting'=>'Hello {FIRSTNAME},','body'=>'Join us for our next birding webinar.','upcoming'=>true];$composed=$campaigns->composeHtml(['payload'=>json_encode($payload)]);
file_put_contents($dir.'/preview.html','<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Newsletter preview</title><body>'.$composed.'</body></html>');
echo "PASS real 600px thumbnail, smaller bytes, read policy, external-image omission, labels, banner order, escaping, links, scope and frozen previews.\n";
