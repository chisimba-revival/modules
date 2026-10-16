<?php
/** ShortURL administration and public redirects. Charl Mert / AVOIR, GNU GPL. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
class shorturl extends controller
{
    public $repository, $policy, $language;
    public function init()
    {
        $this->policy=$this->getObject('shorturlsecurity','shorturl');
        $this->language=$this->getObject('language','language');
        $this->repository=$this->getObject('dbmap','shorturl');
    }
    public function requiresLogin($action=null) { return $this->getParam('action','list') !== 'redirect'; }
    public function text(string $key): string { return $this->language->languageText('mod_shorturl_'.$key,'shorturl'); }
    public function dispatch()
    {
        $action=$this->getParam('action','list');
        $input=[];
        foreach (['id','revision','csrf_token','txtMatchUrl','txtTargetUrl','ordering','confirm'] as $key) $input[$key]=$this->getParam($key,$key==='ordering'?'0':'');
        $json=str_contains($_SERVER['HTTP_ACCEPT']??'','application/json');
        try {
            $this->policy->check($action,$input,$_SERVER['REQUEST_METHOD']??'');
            if ($action==='redirect') {
                $root=$this->getObject('altconfig','config')->getItem('KEWL_SITE_ROOT');
                $path=(string)(parse_url($root,PHP_URL_PATH)??'/');
                $request=$_SERVER['REQUEST_URI']??'';
                $relative=str_starts_with($request,$path)?substr($request,strlen($path)):$request;
                $target=$this->repository->getTarget($relative)??$root;
                if (!dbmap::safeTarget($target) || str_contains($target,'module=shorturl&action=redirect')) throw new DomainException('invalid');
                header('Location: '.$target, true, 302);
                return null;
            }
            if ($action==='save') {
                $result=$this->repository->saveMapping($input);
                if ($json) $this->respond(['ok'=>true]+$result);
                return $this->nextAction('list');
            }
            if ($action==='delete') {
                $this->repository->deleteMapping($input['id'],$input['revision']);
                if ($json) $this->respond(['ok'=>true]);
                return $this->nextAction('list');
            }
            if ($action==='edit') {
                $row=$this->repository->findMapping($input['id']);
                if (!$row) throw new DomainException('missing');
                if ((int)$row['is_dynamic']!==0) throw new DomainException('dynamic');
                $input=['id'=>$row['id'],'revision'=>$this->repository->revision($row),'txtMatchUrl'=>$row['match_url'],'txtTargetUrl'=>$row['target_url'],'ordering'=>(string)$row['ordering']];
            }
        } catch (Throwable $error) {
            $key=$error instanceof DomainException?$error->getMessage():'savefailed';
            if (!in_array($key,['denied','invalid','method','csrf','stale','dynamic','duplicate','missing','savefailed'],true)) $key='savefailed';
            http_response_code(match($key){'method'=>405,'denied','csrf'=>403,'stale'=>409,'missing'=>404,default=>422});
            if ($json) $this->respond(['ok'=>false,'message'=>$this->text('request_'.$key)]);
            // Denied requests never render the private list. Save errors retain posted fields.
            if (in_array($key,['denied','method'],true) || ($key==='invalid' && (!in_array($action,['save','delete'],true) || count(array_filter($input,'is_string'))!==count($input)))) {
                header('Content-Type: text/plain; charset=UTF-8');
                die($this->text('request_'.$key));
            }
            $this->setVar('shorturlError',$this->text('request_'.$key));
        }
        $this->policy->requireAdmin();
        $this->setVar('shorturlInput',$input);
        $this->setVar('shorturlRows',$this->repository->listMappings());
        $this->appendArrayVar('headerParams',$this->getJavaScriptFile('editor.js','shorturl'));
        return 'manage_tpl.php';
    }
    private function respond(array $data): never
    {
        header('Content-Type: application/json; charset=UTF-8');
        die(json_encode($data,JSON_THROW_ON_ERROR));
    }
}
