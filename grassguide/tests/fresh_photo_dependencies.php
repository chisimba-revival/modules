<?php
/** Photo requests start before the editor/service has been loaded. */
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {
    public function init() {}
    public function getObject($class,$module=null){
        static $objects=[];
        if(isset($objects[$class]))return $objects[$class];
        if($class==='grassguidestore')return new class {
            public function mediaOne($id){return ['id'=>$id,'payload'=>'{"_file_id":"stored-file","state":"active"}'];}
        };
        if($class==='user')return new class {public function isLoggedIn(){return true;}public function isAdmin(){return true;}};
        if($class==='modulefilestore')return new class extends ChisimbaObject {
            public function readMedia($module,$class,$resource,$file){
                if(!$this->getObject($class,$module)->mayDownloadFile(['id'=>$file],$resource))throw new RuntimeException('Policy denied');
                return 'fixture bytes';
            }
        };
        if($module!=='grassguide')throw new RuntimeException('Unexpected dependency');
        require_once dirname(__DIR__).'/classes/'.$class.'_class_inc.php';
        $object=new $class();$objects[$class]=$object;$object->init();return $object;
    }
}
if(class_exists('grassguideservice',false))throw new RuntimeException('Service preloaded');
$loader=new ChisimbaObject();
if($loader->getObject('grassguidephotos','grassguide')->read('photo')!=='fixture bytes')throw new RuntimeException('Fresh photo request failed');
echo "PASS fresh photo request: lazy service dependencies and file policy loaded.\n";
