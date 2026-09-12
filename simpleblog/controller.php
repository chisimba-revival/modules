<?php
/** Chisimba publishing controller. Canvas design and legacy mutation routes are retired. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class simpleblog extends controller
{
    private $service;
    private $policy;
    private $user;
    private $csrf;
    public function init()
    {
        $this->service=$this->getObject('publishingservice');
        $this->policy=$this->getObject('publishingpolicy');
        $this->user=$this->getObject('user','security');
        $this->csrf=$this->getObject('nativeauthwebcomposition','security')->build()['csrf'];
    }
    public function requiresLogin($action=null)
    { return !in_array($this->getParam('action','view'),array('view','ajaxById','ajaxByTag','getfeed'),true); }
    private function param($name,$default='')
    { $value=$this->getParam($name,$default); return is_string($value)?trim($value):''; }
    public function dispatch($action=null)
    {
        $action=$this->param('action','view');
        $id=$this->param('id',$this->param('postid'));
        $type=$this->param('scope','site'); $scope=$this->param('blogid','site');
        // Old personal/course links keep their explicit blog identity, never the session context.
        if ($type==='site' && $scope!=='site') $type=$this->getObject('dbcontext','context')->getContextDetails($scope)?'context':'personal';
        $post=$id?$this->getObject('publishingstore')->post($id):null;
        if ($id!=='' && !$post) return $this->unavailable();
        if ($post) { $type=$post['post_type']; $scope=$post['blogid']; }
        $this->setVar('blogType',$type); $this->setVar('blogScope',$scope);
        $renderer=$this->getObject('publishingrenderer');
        $crumb='<a href="'.publishingrenderer::escape($this->uri(array('scope'=>$type,'blogid'=>$scope),'simpleblog')).'">'.publishingrenderer::escape($renderer->identity($type,$scope)).'</a>';
        $this->getObject('tools','toolbar')->replaceBreadCrumbs(array($crumb,publishingrenderer::escape($renderer->text(in_array($action,array('edit','manage','preview'),true)?$action:'posts'))));
        $this->setVar('blogError',''); $this->setVar('blogPost',$post);
        if (!$this->policy->validScope($type,$scope)) return $this->unavailable();
        if (in_array($action,array('save','delete'),true)) {
            header('Cache-Control: private, no-store');
            if (($_SERVER['REQUEST_METHOD']??'')!=='POST' || !$this->csrf->consume('simpleblog_publish',$this->param('csrf_token'))) {
                http_response_code(403); $this->setVar('blogError','expired');
                if ($action==='save') { $this->setVar('blogInput',$this->input()); return $this->editor($post,$type,$scope); }
                return 'message_tpl.php';
            }
            try {
                if ($action==='delete') { $this->service->delete($id); return $this->nextAction('manage',array('scope'=>$type,'blogid'=>$scope)); }
                // Submitted scope must also match persisted scope on edits.
                $id=$this->service->save($id,$this->param('scope'),$this->param('blogid'),$this->input());
                return $this->nextAction('preview',array('id'=>$id,'saved'=>'1'));
            } catch (DomainException $e) {
                http_response_code(400); $this->setVar('blogError',$e->getMessage());
                $this->setVar('blogInput',$this->input()); return $this->editor($post,$type,$scope);
            } catch (RuntimeException $e) {
                http_response_code(500); $this->setVar('blogError','save_failed');
                $this->setVar('blogInput',$this->input());return $this->editor($post,$type,$scope);
            }
        }
        if ($action==='edit') return $this->editor($post,$type,$scope);
        if ($action==='preview' || $id!=='') {
            if (!in_array($action,array('preview','view','ajaxById'),true)) return $this->unavailable();
            $post=$this->service->read($id,$action==='preview');
            if (!$post) return $this->unavailable();
            if ($action==='preview') header('Cache-Control: private, no-store');
            $this->setVar('blogPost',$post); $this->setVar('blogPreview',$action==='preview');
            return 'article_tpl.php';
        }
        if (!in_array($action,array('view','manage','ajaxByTag','getfeed'),true)) return $this->unavailable();
        if ($action==='manage' && !$this->policy->canCreate($type,$scope)) return $this->unavailable();
        if ($action!=='manage' && !$this->policy->canRead(array('post_type'=>$type,'blogid'=>$scope,'post_status'=>'posted'))) return $this->unavailable();
        if ($action==='getfeed') {
            header('Content-Type: application/rss+xml; charset=UTF-8');
            echo $this->getObject('publishingrenderer')->feed($type,$scope); exit;
        }
        $this->setVar('blogManage',$action==='manage');
        $this->setVar('blogStatus',in_array($this->param('status'),array('draft','posted'),true)?$this->param('status'):'all');
        return 'posts_tpl.php';
    }
    private function input()
    { return array('title'=>$this->param('title'),'content'=>$this->param('content'),'tags'=>$this->param('tags'),'status'=>$this->param('status'),'version'=>$this->param('version')); }
    private function editor($post,$type,$scope)
    {
        if ($post ? !$this->policy->canEdit($post) : !$this->policy->canCreate($type,$scope)) return $this->unavailable();
        header('Cache-Control: private, no-store');
        $this->setVar('blogToken',$this->csrf->issue('simpleblog_publish'));
        return 'editor_tpl.php';
    }
    private function unavailable()
    { http_response_code(404); $this->setVar('blogError','unavailable'); return 'message_tpl.php'; }
}
