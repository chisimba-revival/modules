<?php
/** Lowveld and Kruger editorial workspace. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class grassguide extends controller
{
    public function init() {}
    public function requiresLogin($action = null) { return ($action ?? $this->param('action','index'))!=='catalogue' && !in_array(($action ?? $this->param('action','index')),['photo','premiumphoto','premiumcatalogue','premiumstatus','checklistsync','sightingssync'],true); }
    private function param(string $key, string $default = ''): string { $v=$this->getParam($key,$default); return is_string($v) ? $v : $default; }
    private function csrf() { return $this->getObject('nativeauthwebcomposition','security')->build()['csrf']; }
    public function dispatch($action = null)
    {
        if($this->param('action','index')==='catalogue') {
            try { $response=$this->getObject('grassguiderights','grassguide')->publicResponse($_SERVER['REQUEST_METHOD']??'',$_SERVER['HTTP_IF_NONE_MATCH']??''); }
            catch(Throwable $e) { error_log('Grass catalogue unavailable ('.get_class($e).').');$response=['status'=>503,'headers'=>['Content-Type'=>'application/json','Cache-Control'=>'no-store','Access-Control-Allow-Origin'=>'*'],'body'=>'{"error":"unavailable"}']; }
            http_response_code($response['status']);foreach($response['headers'] as $name=>$value)header($name.': '.$value);echo $response['body'];exit;
        }
        if($this->param('action','index')==='premiumcatalogue') {
            header('Content-Type: application/json; charset=UTF-8');header('Cache-Control: no-store');header('Access-Control-Allow-Origin: *');header('Access-Control-Allow-Headers: X-API-Key');header('Access-Control-Allow-Methods: GET, OPTIONS');header('X-Content-Type-Options: nosniff');
            $method=$_SERVER['REQUEST_METHOD']??'';if($method==='OPTIONS'){http_response_code(204);exit;}if($method!=='GET'){http_response_code(405);exit;}
            try{echo json_encode($this->getObject('grassguiderights','grassguide')->premiumExport($_SERVER['HTTP_X_API_KEY']??''),JSON_THROW_ON_ERROR);}
            catch(DomainException $e){http_response_code(403);echo '{"error":"premium_required"}';}
            catch(Throwable $e){http_response_code(503);echo '{"error":"unavailable"}';}
            exit;
        }
        if($this->param('action','index')==='sightingssync') {
            try{$response=$this->getObject('grassguidepremium','grassguide')->sightingsResponse($_SERVER['REQUEST_METHOD']??'',is_string($_SERVER['HTTP_X_API_KEY']??null)?$_SERVER['HTTP_X_API_KEY']:'',file_get_contents('php://input',false,null,0,65537));}
            catch(Throwable $e){error_log('Grass sightings unavailable ('.get_class($e).').');$response=['status'=>503,'headers'=>['Content-Type'=>'application/json','Cache-Control'=>'no-store','Access-Control-Allow-Origin'=>'*'],'body'=>'{"error":"unavailable"}'];}
            http_response_code($response['status']);foreach($response['headers'] as $name=>$value)header($name.': '.$value);echo $response['body'];exit;
        }
        if($this->param('action','index')==='checklistsync') {
            try{$response=$this->getObject('grassguidepremium','grassguide')->checklistResponse($_SERVER['REQUEST_METHOD']??'',is_string($_SERVER['HTTP_X_API_KEY']??null)?$_SERVER['HTTP_X_API_KEY']:'',file_get_contents('php://input',false,null,0,32769));}
            catch(Throwable $e){error_log('Grass checklist unavailable ('.get_class($e).').');$response=['status'=>503,'headers'=>['Content-Type'=>'application/json','Cache-Control'=>'no-store','Access-Control-Allow-Origin'=>'*'],'body'=>'{"error":"unavailable"}'];}
            http_response_code($response['status']);foreach($response['headers'] as $name=>$value)header($name.': '.$value);echo $response['body'];exit;
        }
        if($this->param('action','index')==='premiumstatus') {
            try{$response=$this->getObject('grassguidepremium','grassguide')->response($_SERVER['REQUEST_METHOD']??'',is_string($_SERVER['HTTP_X_API_KEY']??null)?$_SERVER['HTTP_X_API_KEY']:'');}
            catch(Throwable $e){error_log('Grass premium unavailable ('.get_class($e).').');$response=['status'=>503,'headers'=>['Content-Type'=>'application/json','Cache-Control'=>'no-store','Access-Control-Allow-Origin'=>'*'],'body'=>'{"error":"unavailable"}'];}
            http_response_code($response['status']);foreach($response['headers'] as $name=>$value)header($name.': '.$value);echo $response['body'];exit;
        }
        if(in_array($this->param('action','index'),['photo','premiumphoto'],true)) {
            header('Cache-Control: private, no-store');header('Access-Control-Allow-Origin: *');header('X-Content-Type-Options: nosniff');
            $method=$_SERVER['REQUEST_METHOD']??'';
            if($method==='OPTIONS'){header('Access-Control-Allow-Methods: GET, HEAD, OPTIONS');header('Access-Control-Allow-Headers: X-API-Key');http_response_code(204);exit;}
            if(!in_array($method,['GET','HEAD'],true)){header('Allow: GET, HEAD, OPTIONS');http_response_code(405);exit;}
            try{$photos=$this->getObject('grassguidephotos','grassguide');$bytes=$this->param('action')==='premiumphoto'?$photos->readPremium($this->param('id'),$_SERVER['HTTP_X_API_KEY']??''):$photos->read($this->param('id'));}
            catch(DomainException $e){http_response_code(404);exit;}
            catch(Throwable $e){error_log('Grass photo unavailable ('.get_class($e).').');http_response_code(503);exit;}
            header('Content-Type: '.(new finfo(FILEINFO_MIME_TYPE))->buffer($bytes));header('Content-Length: '.strlen($bytes));
            if($method==='GET')echo $bytes;exit;
        }
        header('Cache-Control: private, no-store');
        $service=$this->getObject('grassguideservice','grassguide');
        if (!$service->allowed()) { http_response_code(403); return 'denied_tpl.php'; }
        $action=$this->param('action','index');
        if (!in_array($action,['removemediabatch','premium','createpremiumkey','revokepremiumkey','photos','uploadphotos','resumenc','index','setup','edit','membership','setmembership','media','save','savemedia','import','enrich','export','rights','contributors','savecontact','queuepermission','contributorsexport','rightstask','saverights','savepolicy','appexport','commercialpreview'],true)) { http_response_code(404); return 'denied_tpl.php'; }
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
        $write=in_array($action,['removemediabatch','createpremiumkey','revokepremiumkey','uploadphotos','resumenc','savecontact','queuepermission','setmembership','save','savemedia','import','enrich','saverights','savepolicy'],true);
        if ($write) {
            $input=$_POST;
            try {
                if (($_SERVER['REQUEST_METHOD'] ?? '')!=='POST') { http_response_code(405); header('Allow: POST'); throw new DomainException('method'); }
                if (!$this->csrf()->consume('grassguide-admin',$this->param('csrf_token'))) { http_response_code(403); throw new DomainException('expired'); }
                if ($action==='createpremiumkey') $record=$this->getObject('grassguidepremium','grassguide')->create($input);
                elseif ($action==='revokepremiumkey') $this->getObject('grassguidepremium','grassguide')->revoke($input);
                elseif ($action==='uploadphotos') $record=$this->getObject('grassguidephotos','grassguide')->upload($id,$input,$_FILES['photos']??[]);
                elseif ($action==='resumenc') $record=$this->getObject('grassguidephotos','grassguide')->resume($id,$input);
                elseif ($action==='savecontact') $record=$this->getObject('grassguidecontacts','grassguide')->save($id,$input);
                elseif ($action==='queuepermission') $record=$this->getObject('grassguidecontacts','grassguide')->queue($id,$input);
                elseif ($action==='savepolicy') $record=$this->getObject('grassguiderights','grassguide')->savePolicy($input);
                elseif ($action==='saverights') $record=$this->getObject('grassguiderights','grassguide')->saveTask($this->param('target'),$input);
                elseif ($action==='setmembership') $record=$service->setMembership($id,$input);
                elseif ($action==='save') $record=$service->save($id,$input);
                elseif ($action==='removemediabatch') $record=$service->removeMediaBatch($id,$input);
                elseif ($action==='savemedia') $record=($input['licence']??'')==='copyright'&&($input['state']??'')==='active'?$this->getObject('grassguidephotos','grassguide')->saveCopyrightMedia($species,$id,$input):$service->saveMedia($species,$id,$input);
                elseif ($action==='enrich') $message=(string)$service->queueEnrichment();
                else $message=(string)$service->import($this->param('seed_hash'));
            } catch (DomainException $e) { $error=$e->getMessage(); if (http_response_code()<400) http_response_code($error==='conflict'?409:422); }
            catch (Throwable $e) { $error='failed'; http_response_code(500); error_log('Grass guide write failed ('.get_class($e).').'); }
            if ($this->param('ajax')==='1') {
                $r=$this->getObject('grassguiderenderer','grassguide');
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode(['ok'=>$error==='','message'=>$error!==''?$r->text($error):(in_array($action,['import','enrich'],true)?sprintf($r->text($action==='enrich'?'enqueued':'imported'),$message):$r->text('saved')),'csrf'=>$this->csrf()->issueForSession('grassguide-admin'),'record'=>$record],JSON_THROW_ON_ERROR); exit;
            }
            if ($error==='' && $action!=='createpremiumkey') {
                if($action==='revokepremiumkey')return $this->nextAction('premium',['saved'=>'1']);
                if(in_array($action,['uploadphotos','resumenc'],true))return $this->nextAction($action==='uploadphotos'?'edit':'photos',['id'=>$id,'saved'=>'1']);
                if(in_array($action,['savecontact','queuepermission'],true))return $this->nextAction('contributors',['contributor'=>$id,($action==='queuepermission'?'queued':'saved')=>'1']);
                if(in_array($action,['saverights','savepolicy'],true))return $this->nextAction($action==='saverights'?'rightstask':'rights',['target'=>$this->param('target'),'saved'=>'1']);
                return $this->nextAction(in_array($action,['import','enrich'],true)?'setup':(in_array($action,['save','setmembership','removemediabatch'],true)?'edit':'media'),['id'=>$id,'species_id'=>$species,'saved'=>'1']);
            }
        }
        $view=['removemediabatch'=>'edit','createpremiumkey'=>'premium','revokepremiumkey'=>'premium','uploadphotos'=>'photos','resumenc'=>'photos','savecontact'=>'contributors','queuepermission'=>'contributors','setmembership'=>'membership','save'=>'edit','savemedia'=>'media','import'=>'setup','enrich'=>'setup','savepolicy'=>'rights','saverights'=>'rightstask'][$action] ?? $action;
        if (in_array($view,['photos','edit','membership'],true) && $id!=='') $record=$service->one($id);
        if (in_array($view,['photos','membership'],true) && !$record) { http_response_code(404); return 'denied_tpl.php'; }
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
        $this->setVar('grassView',$view); $this->setVar('grassRecord',$record); $this->setVar('grassInput',$error!==''&&$action!=='removemediabatch'?$input:null); $this->setVar('grassBatchInput',$action==='removemediabatch'?$input:[]);
        $this->setVar('grassId',$id ?: bin2hex(random_bytes(16))); $this->setVar('grassSpecies',$species);
        $this->setVar('grassQueued',$this->param('queued')==='1');
        $this->setVar('grassError',$error); $this->setVar('grassSaved',$this->param('saved')==='1');
        $this->setVar('grassCsrf',$this->csrf()->issueForSession('grassguide-admin'));
        return 'admin_tpl.php';
    }
}
