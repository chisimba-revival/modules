<?php
/** Controller for ordinary site pages. */
class sitepages extends controller
{
    private $db;
    private $user;
    private $cleaner;
    public function init()
    {
        $this->db=$this->getObject('dbsitepages','sitepages');
        $this->user=$this->getObject('user','security');
        $this->cleaner=$this->getObject('htmlcleaner','utilities');
        $this->setLayoutTemplate('layout_tpl.php');
    }
    public function requiresLogin($action)
    {
        return in_array((string)$action,array('manage','save','archive'),true);
    }
    public function dispatch($action)
    {
        $action=(string)$action;
        // Public entry routes stay public for every role; management is explicit.
        return match($action){'manage'=>$this->manage(),'save'=>$this->save(),'archive'=>$this->archive(),default=>$this->view()};
    }
    private function canManage(){return $this->user->isAdmin();}
    private function text($key){return $this->getObject('language','language')->languageText('mod_sitepages_'.$key,'sitepages');}
    private function token()
    {
        $token=(string)$this->getSession('sitepages_csrf');
        if($token===''){$token=bin2hex(random_bytes(24));$this->setSession('sitepages_csrf',$token);}
        return $token;
    }
    private function validPost()
    {
        $expected=(string)$this->getSession('sitepages_csrf');$actual=(string)$this->getParam('csrf_token','');
        return ($_SERVER['REQUEST_METHOD']??'GET')==='POST'&&$expected!==''&&hash_equals($expected,$actual);
    }
    private function flash($message){$this->setSession('sitepages_flash',$message);}
    private function labels()
    {
        $labels=array();foreach(array('title','intro','new','edit','archive','preview','save','cancel','pagetitle','slug','slughelp','content','status','draft','published','empty','forbidden','confirmarchive') as $key)$labels[$key]=$this->text($key);return $labels;
    }
    private function manage()
    {
        $this->setVar('sitepagesError', false);
        if(!$this->canManage())$this->setVar('sitepagesDenied',true);
        $edit=false;$id=(string)$this->getParam('id','');if($id!==''&&$this->canManage())$edit=$this->db->find($id);
        $this->setVar('sitepagesBlocks',$this->getObject('compositionservice','contentblocks')->fromPost(['composition_json'=>$edit['composition_json']??'','post_content'=>$edit['body_html']??'']));
        $this->setVar('sitepagesLabels',$this->labels());$this->setVar('sitepagesRows',$this->canManage()?$this->db->activeRows():array());$this->setVar('sitepagesEdit',$edit);$this->setVar('sitepagesCsrf',$this->token());$this->setVar('sitepagesFlash',(string)$this->getSession('sitepages_flash'));$this->setSession('sitepages_flash','');return 'manage_tpl.php';
    }
    private function recover(array $input,$message)
    {
        $result=$this->manage();
        $this->setVar('sitepagesEdit',$input);
        $this->setVar('sitepagesBlocks',$input['blocks']??[]);
        $this->setVar('sitepagesActive',$this->getParam('composition_active',''));
        $this->setVar('sitepagesFlash',$this->text($message));
        $this->setVar('sitepagesError', $message !== 'unsaved');
        return $result;
    }
    private function save()
    {
        $input=['id'=>(string)$this->getParam('id',''),'slug'=>strtolower(trim((string)$this->getParam('slug',''))),'title'=>trim((string)$this->getParam('title','')),'body_html'=>(string)$this->getParam('body_html',''),'status'=>$this->getParam('status','draft')==='published'?'published':'draft','blocks'=>$this->getParam('content_blocks',[]),'version'=>(string)$this->getParam('version','')];
        if(!$this->canManage()){http_response_code(403);return $this->manage();}
        if(!$this->validPost()){http_response_code(403);return $this->recover($input,'expired');}
        try {
            if($this->getParam('composition_complete')!=='1')throw new DomainException('invalid');
            $builder=$this->getObject('compositionservice','contentblocks');$input['blocks']=$builder->validate($input['blocks']);
            $command=(string)$this->getParam('compose_command','');
            if($command!==''){
                $before=array_column($input['blocks'],'id');$input['blocks']=$builder->command($input['blocks'],$command);
                $result=$this->recover($input,'unsaved');$parts=explode(':',$command);$active=$this->getParam('composition_active','');
                if(in_array($parts[0],['focus','up','down','before','end','slide','remove_slide'],true))$active=$parts[1];
                foreach($input['blocks'] as $block)if(!in_array($block['id'],$before,true))$active=$block['id'];
                $this->setVar('sitepagesActive',$active);return $result;
            }
            $body=$builder->render($input['blocks']);$collision=$this->db->findBySlug($input['slug']);
            if($input['title']===''||mb_strlen($input['title'])>250||$body===''||!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/',$input['slug'])||($collision&&(string)$collision['id']!==$input['id']))throw new DomainException('invalid');
            $saved=$this->db->savePage(['slug'=>$input['slug'],'title'=>$input['title'],'body_html'=>$body,'status'=>$input['status'],'composition_json'=>json_encode(['version'=>1,'blocks'=>$input['blocks']],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),'_expected_version'=>$input['version']],$this->user->userId(),$input['id']);
            if(!$saved)throw new RuntimeException('failed');
            if($this->getParam('composition_save_ajax')==='1'){
                $input['id']=$saved['id'];$input['version']=dbsitepages::version($saved);
                $result=$this->recover($input,'unsaved');
                $this->setVar('sitepagesSaved',true);$this->setVar('sitepagesFlash',$this->text('saved'));
                return $result;
            }
            $this->flash($this->text('saved'));return $this->nextAction('manage',['id'=>$saved['id']]);
        }catch(DomainException $error){return $this->recover($input,$error->getMessage()==='conflict'?'conflict':'invalid');}
        catch(Throwable $error){return $this->recover($input,'failed');}
    }
    private function archive()
    {
        if($this->canManage()&&$this->validPost()){$this->db->archive((string)$this->getParam('id',''),$this->user->userId());$this->flash($this->text('archived'));}else{$this->flash($this->text('forbidden'));}return $this->nextAction('manage');
    }
    private function view()
    {
        $this->setVar('sitepagesCanEdit', $this->canManage());
        $page=$this->db->findBySlug((string)$this->getParam('slug','home'),!$this->canManage());if(!$page||(!$this->canManage()&&($page['status']??'')!=='published')){http_response_code(404);$this->setVar('sitepagesMissing',$this->text('notfound'));}else {if(!empty($page['composition_json']))$page['body_html']=$this->getObject('compositionservice','contentblocks')->render($this->getObject('compositionservice','contentblocks')->fromPost($page));else $page['body_html']=$this->cleaner->cleanHtml($page['body_html']);$this->setVar('sitepagesPage',$page);}return 'view_tpl.php';
    }
}
?>
