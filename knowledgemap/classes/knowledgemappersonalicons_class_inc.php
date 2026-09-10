<?php
/** Personal Knowledge Map images supplied by File Manager. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');

/** Keep file ownership, storage and delivery in File Manager. */
class knowledgemappersonalicons extends controller
{
    /** List the current user's raster images in the dedicated km-icons folder. */
    public function choices(){
        $userId=(string)$this->getObject('user','security')->userId();
        if(!preg_match('/^[A-Za-z0-9_-]+$/',$userId))return array();
        $folders=$this->getObject('dbfolder','filemanager');
        $folderId=$folders->getFolderId('users/'.$userId.'/km-icons');
        if(!$folderId)return array();
        $result=$this->getObject('fileapi','filemanager')->listUserImages($folderId);
        if(empty($result['ok']))return array();
        return array_values(array_filter($result['files'],fn($file)=>$this->isRaster($file)));
    }

    /** Open the dedicated folder, or the personal root where it can be created. */
    public function manageUrl(){
        $userId=(string)$this->getObject('user','security')->userId();
        if(!preg_match('/^[A-Za-z0-9_-]+$/',$userId))return '';
        $folders=$this->getObject('dbfolder','filemanager');
        $folderId=$folders->getFolderId('users/'.$userId.'/km-icons');
        if(!$folderId)$folderId=$folders->getFolderId('users/'.$userId);
        return html_entity_decode($this->uri(array('action'=>'viewfolder','folder'=>$folderId),'filemanager'),ENT_QUOTES,'UTF-8');
    }

    /** Restrict custom icon content to File Manager's supported raster formats. */
    public function isRaster(array $file){
        return in_array(strtolower((string)($file['mimetype']??'')),array('image/png','image/jpeg','image/gif','image/webp','image/bmp'),true)
            && preg_match('/^[A-Za-z0-9_-]+$/',(string)($file['id']??''));
    }

    /** Build escaped image markup; the existing file endpoint controls delivery. */
    public function markup(array $file){
        if(!$this->isRaster($file))return '';
        $e=fn($value)=>htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');
        return '<img class="knowmap-personal-icon" src="'.$e($file['url']).'" alt="" draggable="false" loading="lazy" />';
    }

    /** Resolve only referenced icons; private personal files remain owner-only. */
    public function library(array $document){
        $library='';$seen=array();$userId=(string)$this->getObject('user','security')->userId();
        $files=$this->getObject('dbfile','filemanager');$folders=$this->getObject('dbfolder','filemanager');
        $access=$this->getObject('folderaccess','filemanager');
        foreach($document['nodes']??array() as $node){
            $icon=$node['presentation']['icon']??'';
            if(!preg_match('/^file:([A-Za-z0-9_-]+)$/',(string)$icon,$match)||isset($seen[$icon]))continue;
            $seen[$icon]=true;$file=$files->getFile($match[1]);
            if(!is_array($file)||!$this->isRaster($file)||!preg_match('~^users/([A-Za-z0-9_-]+)/km-icons$~',(string)($file['filefolder']??''),$owner))continue;
            $folder=$folders->getFolder($folders->getFolderId($file['filefolder']));
            if(!$folder)continue;
            if($owner[1]!==$userId&&($access->isFileAccessPrivate($file)||$access->isFileVisibilityPrivate($file)||$access->isFileAccessPrivate($folder)||$access->isFileVisibilityPrivate($folder)))continue;
            $file['url']=html_entity_decode($this->uri(array('action'=>'file','id'=>$file['id'],'filename'=>$file['filename']),'filemanager'),ENT_QUOTES,'UTF-8');
            $library.='<span data-knowmap-icon="'.htmlspecialchars($icon,ENT_QUOTES,'UTF-8').'">'.$this->markup($file).'</span>';
        }
        return $library;
    }
}
