<?php
/**
* Workgroup Admin module
* @author Jeremy O'Connor
* @copyright 2004 University of the Western Cape
* $Id$
*/
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
/** The membership editor uses the same current scope boundary as file sharing. */
class workgroupadmin extends controller
{
    public function dispatch($action=null) {
        $service=$this->getObject('sharingservice','workgroup');
        $csrf=$this->getObject('nativeauthwebcomposition','security')->build()['csrf'];
        $this->setVar('service',$service);$this->setVar('error','');
        try {
            $context=$service->context();
            if (!$service->manager($context)) throw new RuntimeException('access',403);
            $purpose='workgroup_admin_'.substr(hash('sha256',$context),0,24);
            $this->setVar('csrfToken',$csrf->issue($purpose));
            $creating=in_array($action,['create','createconfirm'],true);
            $id=$this->getParam('workgroupId','');
            if ($creating || in_array($action,['manage','rename','renameconfirm','processform'],true)) {
                $row=$creating?['id'=>bin2hex(random_bytes(16)),'description'=>'']:$service->group($id);
                $draft=['id'=>$row['id'],'description'=>$row['description'],'members'=>$creating?[]:$service->repo->members($id),
                    'revision'=>$creating?'':$service->groupRevision($row)];
                $this->setVar('creating',$creating);$this->setVar('candidates',$service->candidates());
                if (in_array($action,['createconfirm','renameconfirm','processform'],true)) {
                    if (($_SERVER['REQUEST_METHOD']??'')!=='POST') throw new RuntimeException('method',405);
                    $draft=array_intersect_key($_POST,$draft);$draft['members']=$draft['members']??[];
                    $this->setVar('draft',$draft);
                    try {
                        if (!is_string($_POST['csrf_token']??null)||!$csrf->consume($purpose,$_POST['csrf_token'])) throw new RuntimeException('token',403);
                        if (!$creating && ($draft['id']??null)!==$id) throw new RuntimeException('access',403);
                        $id=$service->saveGroup($draft,$creating);
                        return $this->nextAction('manage',['workgroupId'=>$id,'saved'=>'1']);
                    } catch(Throwable $e) {$this->setVar('error',$this->failure($e));return 'native_edit_tpl.php';}
                }
                $this->setVar('draft',$draft);return 'native_edit_tpl.php';
            }
            if ($action==='delete') {
                $group=$service->group($id);$this->setVar('group',$group);
                if (($_SERVER['REQUEST_METHOD']??'')==='POST') {
                    if (!is_string($_POST['csrf_token']??null)||!$csrf->consume($purpose,$_POST['csrf_token'])||($_POST['confirm']??'')!=='yes') throw new RuntimeException('token',403);
                    $service->deleteGroup($id,is_string($_POST['revision']??null)?$_POST['revision']:'');
                    return $this->nextAction(null,['saved'=>'1']);
                }
                return 'native_delete_tpl.php';
            }
            if (!in_array($action,[null,'','confirm'],true)) throw new RuntimeException('missing',404);
            $this->setVar('groups',$service->groups());return 'native_groups_tpl.php';
        } catch(Throwable $e) {$this->setVar('error',$this->failure($e));return 'native_error_tpl.php';}
    }
    private function failure(Throwable $e) {
        $code=in_array($e->getCode(),[403,404,405,409,422],true)?$e->getCode():500;http_response_code($code);
        if($code===500)error_log('Workgroup membership operation failed.');
        return in_array($e->getMessage(),['context','access','missing','conflict','invalid','token','method'],true)?$e->getMessage():'write';
    }
}
