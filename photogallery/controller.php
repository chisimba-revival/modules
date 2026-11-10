<?php
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class photogallery extends controller
{
    public function init(){$this->setLayoutTemplate('layout_tpl.php');}
    public function requiresLogin($action){return false;}
    public function dispatch($action)
    {
        $s=$this->getObject('photoservice','photogallery');$id=$this->getParam('id','flickr');$row=is_string($id)?$s->gallery($id):null;$notice='';
        if(!$row){http_response_code(404);$this->setVar('photoGallery',null);return 'view_tpl.php';}
        $csrf=$this->getObject('nativeauthwebcomposition','security')->build()['csrf'];
        if($action==='refresh'){
            header('Cache-Control: private, no-store');
            $token=$this->getParam('csrf_token','');
            if(($_SERVER['REQUEST_METHOD']??'')!=='POST'||!$s->canManage()||!is_string($token)||!$csrf->consume('photo_refresh',$token)){http_response_code(403);$notice='forbidden';}
            else try{$s->refresh($id);$notice='refreshed';$row=$s->gallery($id);}catch(DomainException $error){$notice=$error->getMessage();}
        }elseif(!in_array($action,['','view'],true)){http_response_code(404);$row=null;}
        $page=max(1,min(10000,(int)$this->getParam('page',1)));$all=$row?json_decode($row['items'],true):[];
        $this->setVar('photoGallery',$row);$this->setVar('photoItems',array_slice($all,($page-1)*12,12));$this->setVar('photoPage',$page);$this->setVar('photoMore',count($all)>$page*12);$this->setVar('photoNotice',$notice);$this->setVar('photoToken',$s->canManage()?$csrf->issue('photo_refresh'):'');return 'view_tpl.php';
    }
}
