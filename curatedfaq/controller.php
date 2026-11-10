<?php
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class curatedfaq extends controller
{
    private $service;
    private $csrf;
    public function init()
    {
        $this->service=$this->getObject('faqservice','curatedfaq');
        $this->csrf=$this->getObject('nativeauthwebcomposition','security')->build()['csrf'];
        $this->setLayoutTemplate('layout_tpl.php');
    }
    // Save/token explicitly return an unauthenticated error, preserving the open editor.
    public function requiresLogin($action){return in_array($action,['manage','edit'],true);}
    private function param($key,$default=''){$v=$this->getParam($key,$default);return is_string($v)?$v:$default;}
    private function text($key){return $this->getObject('language','language')->code2Txt('mod_curatedfaq_'.$key,'curatedfaq');}
    private function json(array $data,$status=200){http_response_code($status);header('Content-Type: application/json; charset=UTF-8');header('Cache-Control: private, no-store');echo json_encode($data,JSON_THROW_ON_ERROR);exit;}
    private function unavailable($code=404){http_response_code($code);$this->setVar('faqMessage',$this->text($code===403?'forbidden':'unavailable'));return 'message_tpl.php';}
    private function input()
    {
        $in=[];foreach(['id','question','answer','status','revision','display_order','new_category'] as $key)$in[$key]=$this->param($key);
        $in['categories']=$this->getParam('categories',[]);return $in;
    }
    public function dispatch($action)
    {
        $action=$action?:'view';
        if(in_array($action,['manage','edit','save','token'],true)){
            header('Cache-Control: private, no-store');
            if(!$this->service->canManage()){
                if(in_array($action,['save','token'],true))$this->json(['ok'=>false,'message'=>$this->text('signin_again')],403);
                return $this->unavailable(403);
            }
        }
        if($action==='token')$this->json(['token'=>$this->csrf->issue('curatedfaq_save')]);
        if($action==='save'){
            $input=$this->input();$json=str_contains($_SERVER['HTTP_ACCEPT']??'','application/json');
            $error='';$status=400;
            if(($_SERVER['REQUEST_METHOD']??'')!=='POST'||!$this->csrf->consume('curatedfaq_save',$this->param('csrf_token'))){$error='expired';$status=403;}
            else try{
                $saved=$this->service->save($input);
                $url=html_entity_decode($this->uri(['action'=>'edit','id'=>$saved['id'],'saved'=>'1'],'curatedfaq'),ENT_QUOTES,'UTF-8');
                if($json)$this->json(['ok'=>true,'url'=>$url,'message'=>$this->text('saved')]);
                return $this->nextAction('edit',['id'=>$saved['id'],'saved'=>'1']);
            }catch(DomainException $e){$error=in_array($e->getMessage(),['conflict','invalid','empty_answer','forbidden'],true)?$e->getMessage():'category_error';$status=$error==='conflict'?409:400;}
            catch(Throwable $e){$error='failed';$status=500;}
            if($json)$this->json(['ok'=>false,'message'=>$this->text($error)],$status);
            http_response_code($status);return $this->editor($input,$this->text($error));
        }
        if($action==='edit'){
            $id=$this->param('id');$row=$id!==''?$this->service->read($id,true):null;
            if($id!==''&&!$row)return $this->unavailable();
            if($row){$row['categories']=array_column($this->service->itemCategories($id),'id');$row['display_order']=(string)$row['display_order'];$row['revision']=(string)$row['revision'];}
            return $this->editor($row??['id'=>bin2hex(random_bytes(16)),'question'=>'','answer'=>'','status'=>'draft','revision'=>'new','display_order'=>'0','categories'=>[],'new_category'=>'']);
        }
        if($action==='preview'){
            $row=$this->service->read($this->param('id'),true);if(!$row)return $this->unavailable();
            header('Cache-Control: private, no-store');$this->setVar('faqPreview',true);$this->setVar('faqRows',[$row]);$this->setVar('faqCategories',[]);return 'public_tpl.php';
        }
        if(!in_array($action,['view','manage'],true))return $this->unavailable();
        $manage=$action==='manage';$page=max(1,min(10000,(int)$this->param('page','1')));
        $rows=$this->service->listing($manage,$this->param('q'),$this->param('category'),$page);
        $this->setVar('faqMore',count($rows)>20);$this->setVar('faqPage',$page);$this->setVar('faqRows',array_slice($rows,0,20));
        $this->setVar('faqCategories',$this->service->categories($manage));
        return $manage?'manage_tpl.php':'public_tpl.php';
    }
    private function editor(array $row,$error='')
    {
        $this->setVar('faqInput',$row);$this->setVar('faqError',$error);$this->setVar('faqToken',$this->csrf->issue('curatedfaq_save'));
        $this->setVar('faqCategories',$this->service->categories(true));return 'edit_tpl.php';
    }
}
