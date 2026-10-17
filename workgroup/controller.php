<?php
/**
 * Workgroup module
 * @author Jeremy O'Connor, Juliet Mulindwa
 * @copyright 2004 University of the Western Cape
 * $Id$
 * Modified by David Wafula on 2008-01-09
 */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
/** Native, server-rendered file sharing. Legacy routes remain guarded aliases. */
class workgroup extends controller
{
    public function dispatch($action = null)
    {
        $service=$this->getObject('sharingservice','workgroup');
        $csrf=$this->getObject('nativeauthwebcomposition','security')->build()['csrf'];
        $this->setVar('service',$service);
        $this->setVar('error','');
        try {
            $service->context();
            $groupId=$this->getParam('workgroupid',$this->getParam('workgroup',''));
            if ($groupId==='') $groupId=$this->getObject('dbworkgroup','workgroup')->getWorkgroupId()??'';
            if ($action==='leaveworkgroup') { $this->getObject('dbworkgroup')->unsetWorkgroupId();return $this->nextAction(null); }
            if ($action==='joinworkgroup') {
                $service->group($groupId);
                $this->getObject('dbworkgroup')->setWorkgroupId($groupId);
                return $this->nextAction(null,['workgroupid'=>$groupId]);
            }
            if ($groupId==='') { $this->setVar('groups',$service->groups());return 'native_groups_tpl.php'; }
            $group=$service->group($groupId);
            $this->setVar('group',$group);
            $purpose='workgroup_files_'.substr(hash('sha256',$groupId),0,24);
            $this->setVar('csrfToken',$csrf->issue($purpose));
            $id=$this->getParam('fileid','');
            $editing=in_array($action,['editfile','editfileupload'],true);
            if (in_array($action,['upload','uploadDocument','editfile','uploadconfirm','editfileupload'],true)) {
                $row=$editing ? $service->file($id,$groupId,true) : ['id'=>bin2hex(random_bytes(16)),'fileid'=>'','title'=>'','description'=>'','version'=>''];
                $draft=['id'=>$row['id'],'fileupload'=>$row['fileid'],'title'=>$row['title'],'description'=>$row['description'],'version'=>$row['version'],
                    'revision'=>$editing ? $service::revision($row) : ''];
                if (in_array($action,['uploadconfirm','editfileupload'],true)) {
                    if (($_SERVER['REQUEST_METHOD']??'')!=='POST') throw new RuntimeException('method',405);
                    $draft=array_intersect_key($_POST,$draft);
                    $this->setVar('draft',$draft);$this->setVar('editing',$editing);
                    try {
                        if (!is_string($_POST['csrf_token']??null)||!$csrf->consume($purpose,$_POST['csrf_token'])) throw new RuntimeException('token',403);
                        if ($editing && ($draft['id']??null)!==$id) throw new RuntimeException('access',403);
                        $service->saveFile($groupId,$draft,$editing);
                        return $this->nextAction(null,['workgroupid'=>$groupId,'saved'=>'1']);
                    } catch (Throwable $e) {
                        $this->setVar('error',$this->failure($e));
                        return 'native_file_form_tpl.php';
                    }
                }
                $this->setVar('draft',$draft);$this->setVar('editing',$editing);
                return 'native_file_form_tpl.php';
            }
            if ($action==='deletefile') {
                $row=$service->file($id,$groupId,true);
                $this->setVar('deleting',$row);
                if (($_SERVER['REQUEST_METHOD']??'')==='POST') {
                    if (!is_string($_POST['csrf_token']??null)||!$csrf->consume($purpose,$_POST['csrf_token'])||($_POST['confirm']??'')!=='yes') throw new RuntimeException('token',403);
                    $service->deleteFile($groupId,$id,is_string($_POST['revision']??null)?$_POST['revision']:'');
                    return $this->nextAction(null,['workgroupid'=>$groupId,'saved'=>'1']);
                }
                return 'native_delete_tpl.php';
            }
            // Includes old ajaxgetfiles: no unguarded fragment or extra request is needed.
            if (!in_array($action,[null,'','ajaxgetfiles'],true)) throw new RuntimeException('missing',404);
            $this->setVar('files',$service->visibleFiles($groupId));
            return 'native_files_tpl.php';
        } catch (Throwable $e) {
            $this->setVar('error',$this->failure($e));return 'native_error_tpl.php';
        }
    }
    private function failure(Throwable $e) {
        $code=in_array($e->getCode(),[403,404,405,409,422],true)?$e->getCode():500;
        http_response_code($code);
        if ($code===500) error_log('Workgroup file-sharing operation failed.');
        return in_array($e->getMessage(),['context','access','missing','conflict','invalid','fileaccess','token','method'],true)?$e->getMessage():'write';
    }
}
