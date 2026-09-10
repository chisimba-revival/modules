<?php
/** Personal icon ownership, format and escaping regressions. @author Derek Keats */
$GLOBALS['kewl_entry_point_run']=true;
class controller {
    public array $objects=array();
    public function getObject($name,$module=null){return $this->objects[$name];}
    public function uri($params,$module=''){return '/index.php?'.http_build_query($params);}
}
require __DIR__.'/../classes/knowledgemappersonalicons_class_inc.php';
$service=new knowledgemappersonalicons();
$service->objects['user']=new class {function userId(){return 'alice';}};
$service->objects['dbfolder']=new class {function getFolderId($path){return $path;}function getFolder($id){return array('access'=>'public','visibility'=>'visible');}};
$service->objects['fileapi']=new class {function listUserImages($id){if($id!=='users/alice/km-icons')throw new Exception('Wrong personal folder');return array('ok'=>true,'files'=>array(array('id'=>'image_1','mimetype'=>'image/png'),array('id'=>'script','mimetype'=>'image/svg+xml')));}};
$service->objects['dbfile']=new class {
 function getFile($id){return array('id'=>$id,'filename'=>'sample.png','mimetype'=>$id==='svg'?'image/svg+xml':'image/png','filefolder'=>$id==='outside'?'users/alice/documents':($id==='own'?'users/alice/km-icons':'users/bob/km-icons'),'access'=>$id==='private'?'private_all':'public','visibility'=>'visible');}
};
$service->objects['folderaccess']=new class {function isFileAccessPrivate($f){return $f['access']==='private_all';}function isFileVisibilityPrivate($f){return $f['visibility']==='hidden';}};
function check($condition,$message){if(!$condition)throw new Exception($message);echo 'PASS: '.$message."\n";}
check(count($service->choices())===1,'only raster files from current personal folder are listed');
check(!$service->isRaster(array('id'=>'bad"id','mimetype'=>'image/png')),'malformed file IDs rejected');
$html=$service->markup(array('id'=>'own','mimetype'=>'image/png','url'=>'/file?name=" onerror="x'));
check(!str_contains($html,' onerror="')&&str_contains($html,'&quot;'),'image attributes are escaped');
$document=array('nodes'=>array_map(fn($id)=>array('presentation'=>array('icon'=>'file:'.$id)),array('own','own','public','private','outside','svg')));
$html=$service->library($document);
check(substr_count($html,'<img ')===2,'library includes own/public icons once and excludes private foreign, other folders and SVG');
check(str_contains($service->manageUrl(),'action=viewfolder'),'management uses File Manager');
