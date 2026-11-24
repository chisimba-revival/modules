<?php
/** Lowveld and Kruger editorial workspace. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class grassguide extends controller
{
    public function init() {}
    public function requiresLogin($action = null) { return ($action ?? $this->param('action','index'))!=='catalogue'; }
    private function param(string $key, string $default = ''): string { $v=$this->getParam($key,$default); return is_string($v) ? $v : $default; }
    private function csrf() { return $this->getObject('nativeauthwebcomposition','security')->build()['csrf']; }
    public function dispatch($action = null)
    {
        if($this->param('action','index')==='catalogue') {
            try { $response=$this->getObject('grassguiderights','grassguide')->publicResponse($_SERVER['REQUEST_METHOD']??'',$_SERVER['HTTP_IF_NONE_MATCH']??''); }
            catch(Throwable $e) { error_log('Grass catalogue unavailable ('.get_class($e).').');$response=['status'=>503,'headers'=>['Content-Type'=>'application/json','Cache-Control'=>'no-store','Access-Control-Allow-Origin'=>'*'],'body'=>'{"error":"unavailable"}']; }
            http_response_code($response['status']);foreach($response['headers'] as $name=>$value)header($name.': '.$value);echo $response['body'];exit;
        }
        header('Cache-Control: private, no-store');
        $service=$this->getObject('grassguideservice','grassguide');
        if (!$service->allowed()) { http_response_code(403); return 'denied_tpl.php'; }
        $action=$this->param('action','index');
        if (!in_array($action,['index','setup','edit','membership','setmembership','media','save','savemedia','import','enrich','export','rights','contributors','savecontact','queuepermission','contributorsexport','rightstask','saverights','savepolicy','appexport','commercialpreview'],true)) { http_response_code(404); return 'denied_tpl.php'; }
        if ($action==='contributorsexport') {
            if (!in_array($_SERVER['REQUEST_METHOD']??'',['GET','HEAD'],true)) { http_response_code(405); header('Allow: GET, HEAD'); return 'denied_tpl.php'; }
            $groups=$this->getObject('grassguiderights','grassguide')->ncContributors();
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="lowveld-kruger-nc-contributors.csv"');
            header('X-Content-Type-Options: nosniff');
            if (($_SERVER['REQUEST_METHOD']??'')==='HEAD')exit;
            $stream=fopen('php://output','wb');fwrite($stream,"\xEF\xBB\xBF");
            $renderer=$this->getObject('grassguiderenderer','grassguide');
            fputcsv($stream,array_map(fn($key)=>$renderer->text('csv_'.$key),['creator','species','licence','source','image','clearance','status','review']),',','"','');
            foreach($groups as $group)foreach($group['items'] as $item) {
                $cells=[$group['creator'],$item['name'],$item['licence'],$item['source_url'],$item['content']['image_url'],grassguiderights::eligible($item,'commercial')?'Yes':'No',$item['task']['task_status'],$this->uri(['action'=>'rightstask','target'=>$item['target']],'grassguide')];
                fputcsv($stream,array_map([grassguiderights::class,'csvCell'],$cells),',','"','');
            }
            fclose($stream);exit;
        }
        if (in_array($action,['appexport','commercialpreview'],true)) {
            $bundle=$this->getObject('grassguiderights','grassguide')->appExport($action==='commercialpreview'?'commercial':null);
            header('Content-Type: application/json; charset=UTF-8');
            header('Content-Disposition: attachment; filename="lowveld-kruger-app.json"');
            echo json_encode($bundle,JSON_THROW_ON_ERROR|JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit;
        }
        if ($action==='export') {
            header('Content-Type: application/json; charset=UTF-8');
            header('Content-Disposition: attachment; filename="lowveld-kruger-editorial.json"');
            echo json_encode($service->export(),JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit;
        }
        $id=$this->param('id'); $species=$this->param('species_id'); $record=null; $input=null; $error=''; $message='';
        $write=in_array($action,['savecontact','queuepermission','setmembership','save','savemedia','import','enrich','saverights','savepolicy'],true);
        if ($write) {
            $input=$_POST;
            try {
                if (($_SERVER['REQUEST_METHOD'] ?? '')!=='POST') { http_response_code(405); header('Allow: POST'); throw new DomainException('method'); }
                if (!$this->csrf()->consume('grassguide-admin',$this->param('csrf_token'))) { http_response_code(403); throw new DomainException('expired'); }
                if ($action==='savecontact') $record=$this->getObject('grassguidecontacts','grassguide')->save($id,$input);
                elseif ($action==='queuepermission') $record=$this->getObject('grassguidecontacts','grassguide')->queue($id,$input);
                elseif ($action==='savepolicy') $record=$this->getObject('grassguiderights','grassguide')->savePolicy($input);
                elseif ($action==='saverights') $record=$this->getObject('grassguiderights','grassguide')->saveTask($this->param('target'),$input);
                elseif ($action==='setmembership') $record=$service->setMembership($id,$input);
                elseif ($action==='save') $record=$service->save($id,$input);
                elseif ($action==='savemedia') $record=$service->saveMedia($species,$id,$input);
                elseif ($action==='enrich') $message=(string)$service->queueEnrichment();
                else $message=(string)$service->import($this->param('seed_hash'));
            } catch (DomainException $e) { $error=$e->getMessage(); if (http_response_code()<400) http_response_code($error==='conflict'?409:422); }
            catch (Throwable $e) { $error='failed'; http_response_code(500); error_log('Grass guide write failed ('.get_class($e).').'); }
            if ($this->param('ajax')==='1') {
                $r=$this->getObject('grassguiderenderer','grassguide');
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode(['ok'=>$error==='','message'=>$error!==''?$r->text($error):(in_array($action,['import','enrich'],true)?sprintf($r->text($action==='enrich'?'enqueued':'imported'),$message):$r->text('saved')),'csrf'=>$this->csrf()->issueForSession('grassguide-admin'),'record'=>$record],JSON_THROW_ON_ERROR); exit;
            }
            if ($error==='') {
                if(in_array($action,['savecontact','queuepermission'],true))return $this->nextAction('contributors',['contributor'=>$id,($action==='queuepermission'?'queued':'saved')=>'1']);
                if(in_array($action,['saverights','savepolicy'],true))return $this->nextAction($action==='saverights'?'rightstask':'rights',['target'=>$this->param('target'),'saved'=>'1']);
                return $this->nextAction(in_array($action,['import','enrich'],true)?'setup':(in_array($action,['save','setmembership'],true)?'edit':'media'),['id'=>$id,'species_id'=>$species,'saved'=>'1']);
            }
        }
        $view=['savecontact'=>'contributors','queuepermission'=>'contributors','setmembership'=>'membership','save'=>'edit','savemedia'=>'media','import'=>'setup','enrich'=>'setup','savepolicy'=>'rights','saverights'=>'rightstask'][$action] ?? $action;
        if (in_array($view,['edit','membership'],true) && $id!=='') $record=$service->one($id);
        if ($view==='membership' && !$record) { http_response_code(404); return 'denied_tpl.php'; }
        if ($view==='media') {
            if ($id!=='') $record=$service->mediaOne($id);
            if ($record) $species=$record['species_id'];
            if (!$service->one($species)) { http_response_code(404); return 'denied_tpl.php'; }
        }
        if($view==='rightstask') {
            try { $item=$this->getObject('grassguiderights','grassguide')->target($this->param('target')); }
            catch(DomainException $ex) { http_response_code(404);return 'denied_tpl.php'; }
            $this->setVar('grassRightsItem',$item);$record=$item['task'];
        }
        // On a failed new save keep the submitted stable ID and entered values.
        if (in_array($view,['edit','media'],true) && $id!=='' && !$record && !($write && $input && ($input['revision'] ?? '')==='0')) { http_response_code(404); return 'denied_tpl.php'; }
        $this->setVar('grassContributor',in_array($action,['savecontact','queuepermission'],true)?$id:$this->param('contributor'));
        $this->setVar('grassContactInput',$action==='savecontact'&&$error!==''?$input:null);
        $this->setVar('grassView',$view); $this->setVar('grassRecord',$record); $this->setVar('grassInput',$error!==''?$input:null);
        $this->setVar('grassId',$id ?: bin2hex(random_bytes(16))); $this->setVar('grassSpecies',$species);
        $this->setVar('grassQueued',$this->param('queued')==='1');
        $this->setVar('grassError',$error); $this->setVar('grassSaved',$this->param('saved')==='1');
        $this->setVar('grassCsrf',$this->csrf()->issueForSession('grassguide-admin'));
        return 'admin_tpl.php';
    }
}
