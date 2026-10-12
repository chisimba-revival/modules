<?php
error_reporting(E_ALL);
set_error_handler(static function($s,$m,$f,$l){throw new ErrorException($m,0,$s,$f,$l);});
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {
    public function getObject($name,$module) {
        if($name!=='nativeplayer'||$module!=='files')throw new RuntimeException('Unexpected dependency');
        return new class {public function video(...$args){return $args;}};
    }
    public function getParam($name,$default=null){return $name==='url'?'/private?token=unchanged%2Bvalue':$default;}
}
$root=dirname(__DIR__);
require $root.'/html5elements/classes/video_class_inc.php';
$v=new video();$v->setVideo(180,320,'/private?token=unchanged%2Bvalue');
if($v->show()!==['/private?token=unchanged%2Bvalue',320,180])throw new RuntimeException('HTML5 wrapper changed media route');
require $root.'/stream__PAUSED/classes/streamapplet_class_inc.php';
if((new streamapplet())->show()!==['/private?token=unchanged%2Bvalue',320,240])throw new RuntimeException('Paused stream changed media route');
foreach(['html5elements/resources/flowplayer-3.1.5.swf','html5elements/resources/flowplayer.controls-3.1.5.swf','html5elements/resources/flowplayer-3.1.4.min.js','html5elements/resources/html5-video.js','stream__PAUSED/resources/cortado-ovt.jar'] as $path){
 if(file_exists($root.'/'.$path))throw new RuntimeException('Retired player returned');
}
echo "PASS: module wrappers use shared native player, preserve encoded URLs, and retired runtimes stay absent\n";
