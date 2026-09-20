<?php
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {function getObject($name,$module){return $GLOBALS['services'][$name];}}
require __DIR__.'/../classes/webinarschedule_class_inc.php';
require __DIR__.'/../classes/webinarannouncements_class_inc.php';
require __DIR__.'/../../contentblocks/classes/contentmediaservice_class_inc.php';
function check($value,$message){if(!$value)throw new RuntimeException($message);}
function row($id,$days,$recording,$extras=[]){return ['id'=>$id,'title'=>'Birds &amp; islands','kind'=>'webinar','status'=>'published','presented_at'=>gmdate('Y-m-d H:i:s',time()+$days*86400),'payload'=>json_encode($extras+['timezone'=>'UTC','recording'=>$recording])];}
$store=new class{public $rows=[];function published($kind){return $this->rows;}};
$GLOBALS['services']=['webinarstore'=>$store,'contentmediaservice'=>new contentmediaservice,'webinarrenderer'=>new class{function text($key){return 'Watch our latest webinar';}}];
$service=new webinarannouncements;
$old=row('old',-10,'https://youtu.be/abcdefghijk');$recent=row('recent',-2,'https://www.youtube.com/watch?v=lmnopqrstuv&feature=shared');
$future=row('future',2,'https://youtu.be/abcdefghijk');$private=row('draft',-1,'https://youtu.be/abcdefghijk');$private['status']='draft';$cancel=row('cancel',-1,'https://youtu.be/abcdefghijk',['cancelled'=>true]);
$store->rows=[$future,$old,$private,$cancel,$recent];
check($service->latestRecordingText()==="Watch our latest webinar\nBirds & islands\nhttps://www.youtube.com/watch?v=lmnopqrstuv",'Latest public completed recording with canonical direct URL');
foreach(['','https://vimeo.com/123456789','javascript:alert(1)','https://youtube.com.evil.test/watch?v=abcdefghijk'] as $url){$store->rows=[$old,row('recent',-2,$url)];check($service->latestRecordingText()==='','No unsupported URL or older fallback');}
$store->rows=[$future,$private,$cancel];check($service->latestRecordingText()==='','No completed public webinar');
echo "PASS latest recording selection, cancellation, privacy, URL validation, omission and no older fallback.\n";
