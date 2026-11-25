<?php
/** Fresh process: do not preload every module class as the PDO suite does. */
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {
    public function getObject($class,$module=null){
        if($module!=='grassguide')throw new RuntimeException('Unexpected dependency');
        require_once dirname(__DIR__).'/classes/'.$class.'_class_inc.php';
        $object=new $class();$object->init();return $object;
    }
}
require dirname(__DIR__).'/classes/grassguideservice_class_inc.php';
if(class_exists('grassguideproviders',false))throw new RuntimeException('Fixture already loaded provider');
$service=new grassguideservice();$service->init();
if(!class_exists('grassguideproviders',false))throw new RuntimeException('Fresh editor must load image helpers');
if(grassguideproviders::photoId(['source_url'=>'https://www.inaturalist.org/photos/412627651'])!=='412627651')throw new RuntimeException('Photo helper unavailable');
echo "PASS fresh service dependencies: image helpers available before editor rendering or media saves.\n";
